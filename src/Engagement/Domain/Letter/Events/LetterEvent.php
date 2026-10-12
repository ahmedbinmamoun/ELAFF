<?php

declare(strict_types=1);

namespace Elaf\Engagement\Domain\Letter\Events;

use DateTimeImmutable;
use Elaf\Engagement\Domain\Letter\ReaderLetterId;
use Elaf\Shared\Domain\Event\DomainEvent;

/**
 * $actorId is null when the reader acts (submitting).
 */
abstract readonly class LetterEvent implements DomainEvent
{
    public function __construct(
        public ReaderLetterId $letterId,
        public ?int $actorId,
        public DateTimeImmutable $at,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }
}
