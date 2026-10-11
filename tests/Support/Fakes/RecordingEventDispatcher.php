<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Domain\Event\DomainEvent;

final class RecordingEventDispatcher implements EventDispatcher
{
    /** @var list<DomainEvent> */
    public array $dispatched = [];

    public function dispatch(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->dispatched[] = $event;
        }
    }

    /**
     * @param  class-string<DomainEvent>  $class
     * @return list<DomainEvent>
     */
    public function ofType(string $class): array
    {
        return array_values(array_filter($this->dispatched, static fn (DomainEvent $e): bool => $e instanceof $class));
    }
}
