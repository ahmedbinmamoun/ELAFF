<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Port;

final readonly class RasterizedPages
{
    /**
     * @param  string  $directory  local temporary folder holding the images
     * @param  list<string>  $images  local image paths, page 1 first
     */
    public function __construct(
        public string $directory,
        public array $images,
    ) {}
}
