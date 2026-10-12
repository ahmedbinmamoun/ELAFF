<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Fail;

final readonly class FailIssueProcessingCommand
{
    public function __construct(
        public int $issueId,
        public string $reason,
    ) {}
}
