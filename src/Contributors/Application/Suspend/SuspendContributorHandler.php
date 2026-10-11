<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\Suspend;

use Elaf\Contributors\Application\ContributorAdmin;
use Elaf\Contributors\Domain\Contributor;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Application\RoleAssignments;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-CON-12: sign-in blocked and writing rights removed; published articles
 * stay online (see UnpublishAllByContributor).
 */
final readonly class SuspendContributorHandler
{
    public function __construct(
        private ContributorAdmin $admin,
        private RoleAssignments $roles,
        private Clock $clock,
    ) {}

    public function handle(SuspendContributorCommand $command): void
    {
        $this->admin->change($command->contributorId, function (Contributor $contributor) use ($command): void {
            $contributor->suspend(UserId::fromInt($command->actorId), $this->clock);
            $this->roles->remove($contributor->id(), Role::Contributor);
        });
    }
}
