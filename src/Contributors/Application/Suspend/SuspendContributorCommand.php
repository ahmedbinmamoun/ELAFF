<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\Suspend;

final readonly class SuspendContributorCommand
{
    public function __construct(
        public int $actorId,
        public int $contributorId,
    ) {}
}
