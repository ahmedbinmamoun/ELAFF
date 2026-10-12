<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad;

use DateTimeImmutable;

/**
 * The ad form. Give $imagePath or $html (an uploaded image wins).
 */
final readonly class AdInput
{
    public function __construct(
        public string $title,
        public string $slot,
        public ?string $imagePath = null,
        public ?string $html = null,
        public ?string $targetUrl = null,
        public ?DateTimeImmutable $startsAt = null,
        public ?DateTimeImmutable $endsAt = null,
        public int $weight = 1,
        public bool $isActive = true,
    ) {}
}
