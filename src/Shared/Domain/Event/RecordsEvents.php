<?php

declare(strict_types=1);

namespace Elaf\Shared\Domain\Event;

/**
 * For aggregate roots: collect events during a domain operation and release
 * them once, after the aggregate has been persisted.
 */
trait RecordsEvents
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    /**
     * @return list<DomainEvent>
     */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    protected function recordThat(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
