<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Port;

final readonly class ImageSize
{
    public function __construct(
        public int $width,
        public int $height,
    ) {}
}
