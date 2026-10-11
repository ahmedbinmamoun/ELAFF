<?php

declare(strict_types=1);

namespace Elaf\Contributors\Domain;

use DateTimeImmutable;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Contributors\Domain\Events\ContributorApproved;
use Elaf\Contributors\Domain\Events\ContributorEmailVerified;
use Elaf\Contributors\Domain\Events\ContributorNameChangeRequested;
use Elaf\Contributors\Domain\Events\ContributorRegistered;
use Elaf\Contributors\Domain\Events\ContributorReinstated;
use Elaf\Contributors\Domain\Events\ContributorRejected;
use Elaf\Contributors\Domain\Events\ContributorRenamed;
use Elaf\Contributors\Domain\Events\ContributorSuspended;
use Elaf\Contributors\Domain\Exception\ContributorRuleViolation;
use Elaf\Contributors\Domain\Exception\IllegalContributorTransition;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Event\RecordsEvents;
use Elaf\Shared\Domain\Time\Clock;

/**
 * An external writer's account (PRD §2.1, FR-CON-01..04, 12). Not one of the
 * four core aggregates, but its status workflow is real behaviour (ADR-0007).
 */
final class Contributor
{
    use RecordsEvents;

    private function __construct(
        private readonly UserId $id,
        private DisplayName $name,
        private ContributorProfile $profile,
        private ContributorStatus $status,
        private readonly DateTimeImmutable $termsAcceptedAt,
        private ?AuthorId $authorId = null,
        private ?UserId $approvedBy = null,
        private ?DateTimeImmutable $approvedAt = null,
        private ?string $rejectionReason = null,
        private ?DisplayName $requestedName = null,
    ) {}

    /**
     * Terms acceptance is mandatory (FR-CON-01): registering records when.
     */
    public static function register(UserId $id, DisplayName $name, ContributorProfile $profile, Clock $clock): self
    {
        $contributor = new self($id, $name, $profile, ContributorStatus::PendingEmail, $clock->now());
        $contributor->recordThat(new ContributorRegistered($id, null, $clock->now()));

        return $contributor;
    }

    public static function reconstitute(
        UserId $id,
        DisplayName $name,
        ContributorProfile $profile,
        ContributorStatus $status,
        DateTimeImmutable $termsAcceptedAt,
        ?AuthorId $authorId,
        ?UserId $approvedBy,
        ?DateTimeImmutable $approvedAt,
        ?string $rejectionReason,
        ?DisplayName $requestedName,
    ): self {
        return new self($id, $name, $profile, $status, $termsAcceptedAt, $authorId, $approvedBy, $approvedAt, $rejectionReason, $requestedName);
    }

    public function verifyEmail(Clock $clock): void
    {
        $this->transitionTo(ContributorStatus::PendingApproval);
        $this->recordThat(new ContributorEmailVerified($this->id, null, $clock->now()));
    }

    /**
     * @param  UserId|null  $editor  null when approval is switched off and the system activates
     * @param  AuthorId  $author  the byline profile created for the contributor (FR-CON-04)
     */
    public function approve(?UserId $editor, AuthorId $author, Clock $clock): void
    {
        $this->transition(ContributorStatus::PendingApproval, ContributorStatus::Active);
        $this->authorId = $author;
        $this->approvedBy = $editor;
        $this->approvedAt = $clock->now();
        $this->recordThat(new ContributorApproved($this->id, $editor, $clock->now()));
    }

    public function reject(UserId $editor, string $reason, Clock $clock): void
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ContributorRuleViolation::rejectionReasonRequired();
        }

        $this->transitionTo(ContributorStatus::Rejected);
        $this->rejectionReason = $reason;
        $this->recordThat(new ContributorRejected($this->id, $editor, $clock->now(), $reason));
    }

    public function suspend(UserId $editor, Clock $clock): void
    {
        $this->transitionTo(ContributorStatus::Suspended);
        $this->recordThat(new ContributorSuspended($this->id, $editor, $clock->now()));
    }

    public function reinstate(UserId $editor, Clock $clock): void
    {
        $this->transition(ContributorStatus::Suspended, ContributorStatus::Active);
        $this->recordThat(new ContributorReinstated($this->id, $editor, $clock->now()));
    }

    public function updateProfile(ContributorProfile $profile): void
    {
        $this->profile = $profile;
    }

    /**
     * A display name change waits for an editor (PRD §2.1). Asking for the
     * current name withdraws a pending request.
     */
    public function requestNameChange(DisplayName $name, Clock $clock): void
    {
        if ($name->equals($this->name)) {
            $this->requestedName = null;

            return;
        }

        $this->requestedName = $name;
        $this->recordThat(new ContributorNameChangeRequested($this->id, null, $clock->now()));
    }

    public function approveNameChange(UserId $editor, Clock $clock): void
    {
        if ($this->requestedName === null) {
            throw ContributorRuleViolation::noPendingNameChange();
        }

        $this->name = $this->requestedName;
        $this->requestedName = null;
        $this->recordThat(new ContributorRenamed($this->id, $editor, $clock->now()));
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function name(): DisplayName
    {
        return $this->name;
    }

    public function profile(): ContributorProfile
    {
        return $this->profile;
    }

    public function status(): ContributorStatus
    {
        return $this->status;
    }

    public function termsAcceptedAt(): DateTimeImmutable
    {
        return $this->termsAcceptedAt;
    }

    public function authorId(): ?AuthorId
    {
        return $this->authorId;
    }

    public function approvedBy(): ?UserId
    {
        return $this->approvedBy;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function rejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function requestedName(): ?DisplayName
    {
        return $this->requestedName;
    }

    /**
     * For moves whose target is reachable from several states: approving and
     * reinstating both lead to Active but start from different places.
     */
    private function transition(ContributorStatus $from, ContributorStatus $to): void
    {
        if ($this->status !== $from) {
            throw new IllegalContributorTransition($this->status, $to);
        }

        $this->transitionTo($to);
    }

    private function transitionTo(ContributorStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new IllegalContributorTransition($this->status, $next);
        }

        $this->status = $next;
    }
}
