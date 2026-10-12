<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Query;

use DateTimeImmutable;

/**
 * A ready issue in lists and cards. Cover = page 1; $coverWidth/$coverHeight
 * are the full image's, the thumbnail has the same proportions.
 */
final readonly class IssueSummary
{
    public function __construct(
        public int $id,
        public int $number,
        public DateTimeImmutable $publishedOn,
        public ?string $title,
        public int $pageCount,
        public string $coverPath,
        public string $coverThumbPath,
        public int $coverWidth,
        public int $coverHeight,
        public int $pdfSize,
    ) {}
}
