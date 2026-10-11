<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

final readonly class CategoryRef
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $icon = null,
    ) {}
}
