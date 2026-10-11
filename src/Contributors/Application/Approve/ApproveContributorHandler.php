<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\Approve;

use Elaf\Contributors\Application\AuthorProfiles;
use Elaf\Contributors\Application\ContributorAdmin;
use Elaf\Contributors\Domain\Contributor;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Application\RoleAssignments;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-CON-03/04: pending_approval → active, with a linked author profile and
 * the contributor role (which grants writing).
 */
final readonly class ApproveContributorHandler
{
    public function __construct(
        private ContributorAdmin $admin,
        private AuthorProfiles $authors,
        private RoleAssignments $roles,
        private Clock $clock,
    ) {}

    public function handle(ApproveContributorCommand $command): void
    {
        $this->admin->change($command->contributorId, function (Contributor $contributor) use ($command): void {
            $author = $contributor->authorId() ?? $this->authors->createFor($contributor->id(), $contributor->name());
            $contributor->approve(UserId::fromInt($command->actorId), $author, $this->clock);
            $this->roles->assign($contributor->id(), Role::Contributor);
        });
    }
}
