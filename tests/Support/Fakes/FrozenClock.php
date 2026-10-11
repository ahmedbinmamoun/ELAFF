<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Elaf\Shared\Domain\Time\Clock;

final class FrozenClock implements Clock
{
    private DateTimeImmutable $now;

    public function __construct(DateTimeImmutable|string $now = '2026-01-01 09:00:00')
    {
        $this->now = $now instanceof DateTimeImmutable
            ? $now
            : new DateTimeImmutable($now, new DateTimeZone('Africa/Khartoum'));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function travelTo(DateTimeImmutable $moment): void
    {
        $this->now = $moment;
    }

    public function advance(string $interval): void
    {
        $this->now = $this->now->add(new DateInterval($interval));
    }
}
