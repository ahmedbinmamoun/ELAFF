<?php

declare(strict_types=1);

namespace Elaf\Shared\Infrastructure\Time;

use DateTimeImmutable;
use DateTimeZone;
use Elaf\Shared\Domain\Time\Clock;

final readonly class SystemClock implements Clock
{
    public function __construct(private DateTimeZone $timezone) {}

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone);
    }
}
