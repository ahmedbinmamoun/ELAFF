<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue;

use Elaf\Publishing\Domain\Issue\Exception\InvalidIssue;

/**
 * One rendered page: full WebP and thumbnail (FR-ISS-02). The full image's
 * size lets the viewer reserve space before it loads.
 */
final readonly class IssuePage
{
    public function __construct(
        public int $number,
        public string $imagePath,
        public string $thumbPath,
        public int $width,
        public int $height,
    ) {
        if ($number < 1) {
            throw InvalidIssue::because('page_not_positive', sprintf('Page number must be positive, got %d.', $number));
        }

        if ($width < 1 || $height < 1) {
            throw InvalidIssue::because('page_size', sprintf('Page %d has no size.', $number));
        }

        if (trim($imagePath) === '' || trim($thumbPath) === '') {
            throw InvalidIssue::because('page_image_missing', sprintf('Page %d has no image.', $number));
        }
    }
}
