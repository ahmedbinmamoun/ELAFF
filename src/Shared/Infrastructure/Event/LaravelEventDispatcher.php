<?php

declare(strict_types=1);

namespace Elaf\Shared\Infrastructure\Event;

use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Domain\Event\DomainEvent;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseTransactionsManager;

final readonly class LaravelEventDispatcher implements EventDispatcher
{
    public function __construct(
        private Dispatcher $events,
        private DatabaseTransactionsManager $transactions,
    ) {}

    public function dispatch(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            // Runs immediately when no transaction is open; discarded on rollback.
            $this->transactions->addCallback(fn () => $this->events->dispatch($event));
        }
    }
}
