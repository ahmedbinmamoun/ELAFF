<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Port;

final readonly class LocalFile
{
    /**
     * @param  string  $checksum  SHA-256, lowercase hex
     */
    public function __construct(
        public bool $isPdf,
        public int $size,
        public string $checksum,
    ) {}
}
