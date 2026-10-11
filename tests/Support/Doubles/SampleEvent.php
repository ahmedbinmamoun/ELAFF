<?php

declare(strict_types=1);

namespace Tests\Support\Doubles;

use DateTimeImmutable;
use Elaf\Shared\Domain\Event\DomainEvent;

final readonly class SampleEvent implements DomainEvent
{
    public function __construct(public string $name, private DateTimeImmutable $at = new DateTimeImmutable('2026-01-01')) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }
}
