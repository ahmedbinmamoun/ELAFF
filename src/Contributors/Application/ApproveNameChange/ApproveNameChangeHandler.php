<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\ApproveNameChange;

use Elaf\Contributors\Application\AuthorProfiles;
use Elaf\Contributors\Application\ContributorAdmin;
use Elaf\Contributors\Domain\Contributor;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * The new display name also becomes the byline name.
 */
final readonly class ApproveNameChangeHandler
{
    public function __construct(
        private ContributorAdmin $admin,
        private AuthorProfiles $authors,
        private Clock $clock,
    ) {}

    public function handle(ApproveNameChangeCommand $command): void
    {
        $this->admin->change($command->contributorId, function (Contributor $contributor) use ($command): void {
            $contributor->approveNameChange(UserId::fromInt($command->actorId), $this->clock);

            if ($contributor->authorId() !== null) {
                $this->authors->rename($contributor->authorId(), $contributor->name());
            }
        });
    }
}
