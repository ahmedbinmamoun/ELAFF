<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Retry;

final readonly class RetryIssueProcessingCommand
{
    public function __construct(
        public int $actorId,
        public int $issueId,
    ) {}
}
