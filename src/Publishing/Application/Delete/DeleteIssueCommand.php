<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Delete;

final readonly class DeleteIssueCommand
{
    public function __construct(
        public int $actorId,
        public int $issueId,
    ) {}
}
