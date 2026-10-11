<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\Reject;

use Elaf\Contributors\Application\ContributorAdmin;
use Elaf\Contributors\Domain\Contributor;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

final readonly class RejectContributorHandler
{
    public function __construct(
        private ContributorAdmin $admin,
        private Clock $clock,
    ) {}

    public function handle(RejectContributorCommand $command): void
    {
        $this->admin->change($command->contributorId, fn (Contributor $c) => $c->reject(UserId::fromInt($command->actorId), $command->reason, $this->clock));
    }
}
