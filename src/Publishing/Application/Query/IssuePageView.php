<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Query;

final readonly class IssuePageView
{
    public function __construct(
        public int $number,
        public string $imagePath,
        public string $thumbPath,
        public int $width,
        public int $height,
    ) {}
}
