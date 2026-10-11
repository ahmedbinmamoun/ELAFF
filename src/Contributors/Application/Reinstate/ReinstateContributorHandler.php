<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\Reinstate;

use Elaf\Contributors\Application\ContributorAdmin;
use Elaf\Contributors\Domain\Contributor;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Application\RoleAssignments;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

final readonly class ReinstateContributorHandler
{
    public function __construct(
        private ContributorAdmin $admin,
        private RoleAssignments $roles,
        private Clock $clock,
    ) {}

    public function handle(ReinstateContributorCommand $command): void
    {
        $this->admin->change($command->contributorId, function (Contributor $contributor) use ($command): void {
            $contributor->reinstate(UserId::fromInt($command->actorId), $this->clock);
            $this->roles->assign($contributor->id(), Role::Contributor);
        });
    }
}
