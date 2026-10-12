<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Ad\Events;

use DateTimeImmutable;
use Elaf\Layout\Domain\Ad\AdId;
use Elaf\Shared\Domain\Event\DomainEvent;

/**
 * An ad was created, changed, switched on/off or deleted ($change says
 * which). Used for the audit trail and, later, cache invalidation.
 */
final readonly class AdChanged implements DomainEvent
{
    public function __construct(
        public AdId $adId,
        public string $change,
        public int $actorId,
        public DateTimeImmutable $at,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }
}
