<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad\Query;

use DateTimeImmutable;

/**
 * An active ad as the public site needs it.
 */
final readonly class AdCandidate
{
    public function __construct(
        public int $id,
        public string $slot,
        public string $title,
        public ?string $imagePath,
        public ?string $html,
        public ?string $targetUrl,
        public int $weight,
        public ?DateTimeImmutable $startsAt,
        public ?DateTimeImmutable $endsAt,
    ) {}

    public function runsAt(DateTimeImmutable $moment): bool
    {
        return ($this->startsAt === null || $moment >= $this->startsAt) && ($this->endsAt === null || $moment < $this->endsAt);
    }
}
