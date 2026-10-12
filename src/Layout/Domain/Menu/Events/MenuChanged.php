<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Menu\Events;

use DateTimeImmutable;
use Elaf\Layout\Domain\Menu\MenuKey;
use Elaf\Shared\Domain\Event\DomainEvent;

final readonly class MenuChanged implements DomainEvent
{
    public function __construct(
        public MenuKey $menu,
        public int $actorId,
        public DateTimeImmutable $at,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }
}
