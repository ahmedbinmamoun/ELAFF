<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\Approve;

final readonly class ApproveContributorCommand
{
    public function __construct(
        public int $actorId,
        public int $contributorId,
    ) {}
}
