<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article;

use DateTimeImmutable;
use Elaf\Content\Domain\Article\Events\ArticleApproved;
use Elaf\Content\Domain\Article\Events\ArticleAssignedToIssue;
use Elaf\Content\Domain\Article\Events\ArticleBreakingCleared;
use Elaf\Content\Domain\Article\Events\ArticleCreated;
use Elaf\Content\Domain\Article\Events\ArticleMarkedBreaking;
use Elaf\Content\Domain\Article\Events\ArticlePromoted;
use Elaf\Content\Domain\Article\Events\ArticlePublished;
use Elaf\Content\Domain\Article\Events\ArticleReturnedToWriter;
use Elaf\Content\Domain\Article\Events\ArticleScheduled;
use Elaf\Content\Domain\Article\Events\ArticleSubmissionWithdrawn;
use Elaf\Content\Domain\Article\Events\ArticleSubmittedForReview;
use Elaf\Content\Domain\Article\Events\ArticleUnpublished;
use Elaf\Content\Domain\Article\Events\ArticleUpdated;
use Elaf\Content\Domain\Article\Exception\ArticleNotPublishable;
use Elaf\Content\Domain\Article\Exception\ArticleWorkflowViolation;
use Elaf\Content\Domain\Article\Exception\IllegalArticleTransition;
use Elaf\Content\Domain\Article\Exception\InvalidArticleContent;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Event\RecordsEvents;
use Elaf\Shared\Domain\Time\Clock;

/**
 * Aggregate root of the Content module (PRD §6.1).
 */
final class Article
{
    use RecordsEvents;

    private function __construct(
        private readonly ArticleId $id,
        private readonly ArticleOrigin $origin,
        private readonly UserId $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private UserId $updatedBy,
        private ArticleContent $content,
        private ArticleStatus $status,
        private ?DateTimeImmutable $publishedAt = null,
        private ?DateTimeImmutable $scheduledFor = null,
        private ?DateTimeImmutable $submittedAt = null,
        private ?UserId $reviewedBy = null,
        private ?string $returnNote = null,
        private bool $breaking = false,
        private ?DateTimeImmutable $breakingUntil = null,
        private bool $featured = false,
        private int $homepagePriority = 0,
        private ?IssuePlacement $issue = null,
    ) {}

    public static function draft(ArticleId $id, ArticleOrigin $origin, UserId $author, ArticleContent $content, Clock $clock): self
    {
        $now = $clock->now();
        $article = new self($id, $origin, $author, $now, $author, $content, ArticleStatus::Draft);
        $article->recordThat(new ArticleCreated($id, $author, $now, $origin));

        return $article;
    }

    /**
     * Rebuilds a persisted article. Repositories only; records no events.
     */
    public static function reconstitute(
        ArticleId $id,
        ArticleOrigin $origin,
        UserId $createdBy,
        DateTimeImmutable $createdAt,
        UserId $updatedBy,
        ArticleContent $content,
        ArticleStatus $status,
        ?DateTimeImmutable $publishedAt,
        ?DateTimeImmutable $scheduledFor,
        ?DateTimeImmutable $submittedAt,
        ?UserId $reviewedBy,
        ?string $returnNote,
        bool $breaking,
        ?DateTimeImmutable $breakingUntil,
        bool $featured,
        int $homepagePriority,
        ?IssuePlacement $issue,
    ): self {
        return new self(
            $id, $origin, $createdBy, $createdAt, $updatedBy, $content, $status, $publishedAt, $scheduledFor,
            $submittedAt, $reviewedBy, $returnNote, $breaking, $breakingUntil, $featured, $homepagePriority, $issue,
        );
    }

    public function revise(ArticleContent $content, UserId $by, Clock $clock): void
    {
        $changes = $content->diff($this->content);

        if ($changes === []) {
            return;
        }

        $this->content = $content;
        $this->updatedBy = $by;
        $this->recordThat(new ArticleUpdated($this->id, $by, $clock->now(), $changes));
    }

    public function submitForReview(UserId $by, Clock $clock): void
    {
        $this->transitionTo(ArticleStatus::InReview, $by);
        $this->submittedAt = $clock->now();
        $this->returnNote = null;
        $this->recordThat(new ArticleSubmittedForReview($this->id, $by, $clock->now()));
    }

    public function withdraw(UserId $by, Clock $clock): void
    {
        $this->transitionTo(ArticleStatus::Draft, $by);
        $this->submittedAt = null;
        $this->recordThat(new ArticleSubmissionWithdrawn($this->id, $by, $clock->now()));
    }

    public function approve(UserId $editor, Clock $clock): void
    {
        if ($editor->equals($this->createdBy)) {
            throw ArticleWorkflowViolation::cannotApproveOwnArticle();
        }

        $this->transitionTo(ArticleStatus::Approved, $editor);
        $this->reviewedBy = $editor;
        $this->recordThat(new ArticleApproved($this->id, $editor, $clock->now()));
    }

    public function returnToWriter(UserId $editor, string $note, Clock $clock): void
    {
        $note = trim($note);

        if ($note === '') {
            throw ArticleWorkflowViolation::returnNoteRequired();
        }

        $this->transitionTo(ArticleStatus::Returned, $editor);
        $this->reviewedBy = $editor;
        $this->returnNote = $note;
        $this->recordThat(new ArticleReturnedToWriter($this->id, $editor, $clock->now(), $note));
    }

    public function schedule(DateTimeImmutable $at, UserId $by, Clock $clock): void
    {
        $this->guardTransitionTo(ArticleStatus::Scheduled);

        if ($at <= $clock->now()) {
            throw ArticleWorkflowViolation::scheduleNotInFuture($at);
        }

        $this->assertPublishable();
        $this->transitionTo(ArticleStatus::Scheduled, $by);
        $this->scheduledFor = $at;
        $this->recordThat(new ArticleScheduled($this->id, $by, $clock->now(), $at));
    }

    /**
     * Publishes now, or at the scheduled time when that time has come.
     * Republishing an unpublished article keeps its original date.
     */
    public function publish(UserId $by, Clock $clock): void
    {
        $now = $clock->now();
        $this->guardTransitionTo(ArticleStatus::Published);
        $this->assertPublishable();

        $publishedAt = $this->publishedAt
            ?? ($this->isDueForPublication($now) ? $this->scheduledFor : null)
            ?? $now;

        if ($publishedAt < $this->createdAt) {
            throw ArticleWorkflowViolation::publishedBeforeCreated();
        }

        $this->transitionTo(ArticleStatus::Published, $by);
        $this->publishedAt = $publishedAt;
        $this->scheduledFor = null;
        $this->recordThat(new ArticlePublished($this->id, $by, $now, $publishedAt));
    }

    public function unpublish(UserId $by, Clock $clock): void
    {
        $this->transitionTo(ArticleStatus::Unpublished, $by);
        $this->recordThat(new ArticleUnpublished($this->id, $by, $clock->now()));
    }

    public function isDueForPublication(DateTimeImmutable $now): bool
    {
        return $this->status === ArticleStatus::Scheduled
            && $this->scheduledFor !== null
            && $this->scheduledFor <= $now;
    }

    /**
     * FR-ART-06: optional expiry after which the ticker drops the article.
     */
    public function markBreaking(?DateTimeImmutable $until, UserId $by, Clock $clock): void
    {
        if ($until !== null && $until <= $clock->now()) {
            throw ArticleWorkflowViolation::breakingExpiryNotInFuture($until);
        }

        $this->breaking = true;
        $this->breakingUntil = $until;
        $this->updatedBy = $by;
        $this->recordThat(new ArticleMarkedBreaking($this->id, $by, $clock->now(), $until));
    }

    public function unmarkBreaking(UserId $by, Clock $clock): void
    {
        if (! $this->breaking) {
            return;
        }

        $this->breaking = false;
        $this->breakingUntil = null;
        $this->updatedBy = $by;
        $this->recordThat(new ArticleBreakingCleared($this->id, $by, $clock->now()));
    }

    public function isBreakingAt(DateTimeImmutable $moment): bool
    {
        return $this->breaking && ($this->breakingUntil === null || $this->breakingUntil > $moment);
    }

    /**
     * FR-ART-07: hero placement on the homepage.
     */
    public function promote(bool $featured, int $homepagePriority, UserId $by, Clock $clock): void
    {
        if ($homepagePriority < 0) {
            throw InvalidArticleContent::negative('homepage priority', $homepagePriority);
        }

        $this->featured = $featured;
        $this->homepagePriority = $homepagePriority;
        $this->updatedBy = $by;
        $this->recordThat(new ArticlePromoted($this->id, $by, $clock->now(), $featured, $homepagePriority));
    }

    public function assignToIssue(?IssuePlacement $placement, UserId $by, Clock $clock): void
    {
        if ($placement === $this->issue || ($placement !== null && $placement->equals($this->issue))) {
            return;
        }

        $this->issue = $placement;
        $this->updatedBy = $by;
        $this->recordThat(new ArticleAssignedToIssue($this->id, $by, $clock->now(), $placement));
    }

    public function id(): ArticleId
    {
        return $this->id;
    }

    public function origin(): ArticleOrigin
    {
        return $this->origin;
    }

    public function createdBy(): UserId
    {
        return $this->createdBy;
    }

    public function isCreatedBy(UserId $user): bool
    {
        return $this->createdBy->equals($user);
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedBy(): UserId
    {
        return $this->updatedBy;
    }

    public function content(): ArticleContent
    {
        return $this->content;
    }

    public function status(): ArticleStatus
    {
        return $this->status;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function scheduledFor(): ?DateTimeImmutable
    {
        return $this->scheduledFor;
    }

    public function submittedAt(): ?DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function reviewedBy(): ?UserId
    {
        return $this->reviewedBy;
    }

    public function returnNote(): ?string
    {
        return $this->returnNote;
    }

    public function isBreaking(): bool
    {
        return $this->breaking;
    }

    public function breakingUntil(): ?DateTimeImmutable
    {
        return $this->breakingUntil;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function homepagePriority(): int
    {
        return $this->homepagePriority;
    }

    public function issue(): ?IssuePlacement
    {
        return $this->issue;
    }

    private function guardTransitionTo(ArticleStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new IllegalArticleTransition($this->status, $next);
        }
    }

    private function transitionTo(ArticleStatus $next, UserId $by): void
    {
        $this->guardTransitionTo($next);
        $this->status = $next;
        $this->updatedBy = $by;
    }

    private function assertPublishable(): void
    {
        $missing = $this->content->missingForPublication();

        if ($missing !== []) {
            throw new ArticleNotPublishable($missing);
        }
    }
}
