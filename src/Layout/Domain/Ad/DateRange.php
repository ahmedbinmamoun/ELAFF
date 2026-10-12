<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Ad;

use DateTimeImmutable;
use Elaf\Layout\Domain\Ad\Exception\InvalidAd;

/**
 * When an ad may run. Open at either end when null.
 */
final readonly class DateRange
{
    public function __construct(
        public ?DateTimeImmutable $startsAt = null,
        public ?DateTimeImmutable $endsAt = null,
    ) {
        if ($startsAt !== null && $endsAt !== null && $endsAt <= $startsAt) {
            throw InvalidAd::because('ad_schedule_order', 'An ad must end after it starts.');
        }
    }

    public function contains(DateTimeImmutable $moment): bool
    {
        return ($this->startsAt === null || $moment >= $this->startsAt)
            && ($this->endsAt === null || $moment < $this->endsAt);
    }

    public function equals(self $other): bool
    {
        return $other->startsAt?->getTimestamp() === $this->startsAt?->getTimestamp()
            && $other->endsAt?->getTimestamp() === $this->endsAt?->getTimestamp();
    }
}
