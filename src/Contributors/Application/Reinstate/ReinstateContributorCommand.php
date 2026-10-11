<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\Reinstate;

final readonly class ReinstateContributorCommand
{
    public function __construct(
        public int $actorId,
        public int $contributorId,
    ) {}
}
