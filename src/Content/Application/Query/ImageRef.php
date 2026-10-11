<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

final readonly class ImageRef
{
    /**
     * @param  string  $path  location on the public disk
     */
    public function __construct(
        public string $path,
        public ?string $caption = null,
        public ?string $credit = null,
    ) {}
}
