<?php

declare(strict_types=1);

namespace Elaf\Contributors\Domain\Events;

use DateTimeImmutable;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Event\DomainEvent;

/**
 * $actorId is null when the contributor acts or the system does
 * (e.g. automatic activation when approval is switched off).
 */
abstract readonly class ContributorEvent implements DomainEvent
{
    public function __construct(
        public UserId $contributorId,
        public ?UserId $actorId,
        public DateTimeImmutable $at,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }
}
