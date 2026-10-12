<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue\Events;

use DateTimeImmutable;
use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Shared\Domain\Event\DomainEvent;

/**
 * $actorId is null when the system acts (the processing job).
 */
abstract readonly class IssueEvent implements DomainEvent
{
    public function __construct(
        public IssueId $issueId,
        public ?UserId $actorId,
        public DateTimeImmutable $at,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }
}
