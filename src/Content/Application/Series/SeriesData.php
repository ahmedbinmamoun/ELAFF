<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Series;

use Elaf\Shared\Domain\Text\Slug;

/**
 * Validated fields of a multi-part series (FR-TAX-03).
 */
final readonly class SeriesData
{
    public function __construct(
        public string $title,
        public Slug $slug,
        public ?string $description = null,
    ) {}
}
