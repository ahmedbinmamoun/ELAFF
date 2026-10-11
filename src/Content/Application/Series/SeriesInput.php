<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Series;

final readonly class SeriesInput
{
    public function __construct(
        public string $title,
        public ?string $slug = null,
        public ?string $description = null,
    ) {}
}
