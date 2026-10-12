<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue\Events;

use DateTimeImmutable;
use Elaf\Publishing\Domain\Issue\IssueId;

final readonly class IssueProcessingFailed extends IssueEvent
{
    public function __construct(IssueId $issueId, DateTimeImmutable $at, public string $reason)
    {
        parent::__construct($issueId, null, $at);
    }
}
