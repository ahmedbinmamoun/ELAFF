<?php

declare(strict_types=1);

namespace Elaf\Shared\Application\Event;

use Elaf\Shared\Domain\Event\DomainEvent;

interface EventDispatcher
{
    /**
     * Implementations must defer delivery until the surrounding transaction
     * (if any) commits, and drop the events if it rolls back.
     */
    public function dispatch(DomainEvent ...$events): void;
}
