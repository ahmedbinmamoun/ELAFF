<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\Reject;

final readonly class RejectContributorCommand
{
    public function __construct(
        public int $actorId,
        public int $contributorId,
        public string $reason,
    ) {}
}
