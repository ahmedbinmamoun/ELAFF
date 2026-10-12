<?php

declare(strict_types=1);

namespace Elaf\Settings\Domain\Events;

use DateTimeImmutable;
use Elaf\Shared\Domain\Event\DomainEvent;

/**
 * PRD §5.8: cached settings are dropped when this is dispatched (FR-SET-02).
 */
final readonly class SettingsChanged implements DomainEvent
{
    /**
     * @param  list<string>  $changedKeys
     */
    public function __construct(
        public int $actorId,
        public array $changedKeys,
        public DateTimeImmutable $at,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }
}
