<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Homepage\Events;

use DateTimeImmutable;
use Elaf\Shared\Domain\Event\DomainEvent;

/**
 * PRD §5.8: the homepage composition changed (cache invalidation, Phase 6).
 */
final readonly class HomepageLayoutChanged implements DomainEvent
{
    public function __construct(
        public int $actorId,
        public DateTimeImmutable $at,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }
}
