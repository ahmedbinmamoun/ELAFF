<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\VerifyEmail;

use Elaf\Contributors\Application\AuthorProfiles;
use Elaf\Contributors\Application\ContributorSettings;
use Elaf\Contributors\Domain\ContributorRepository;
use Elaf\Contributors\Domain\ContributorStatus;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Application\RoleAssignments;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-CON-02/03. The signed link proves ownership of the address; following it
 * twice is harmless. With account approval switched off the contributor is
 * activated right away.
 */
final readonly class VerifyContributorEmailHandler
{
    public function __construct(
        private ContributorRepository $contributors,
        private ContributorSettings $settings,
        private AuthorProfiles $authors,
        private RoleAssignments $roles,
        private Transaction $transaction,
        private EventDispatcher $events,
        private Clock $clock,
    ) {}

    public function handle(VerifyContributorEmailCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $contributor = $this->contributors->get(UserId::fromInt($command->contributorId));

            if ($contributor->status() !== ContributorStatus::PendingEmail) {
                return;
            }

            $contributor->verifyEmail($this->clock);

            if (! $this->settings->requireAccountApproval()) {
                $contributor->approve(null, $this->authors->createFor($contributor->id(), $contributor->name()), $this->clock);
                $this->roles->assign($contributor->id(), Role::Contributor);
            }

            $this->contributors->save($contributor);
            $this->events->dispatch(...$contributor->releaseEvents());
        });
    }
}
