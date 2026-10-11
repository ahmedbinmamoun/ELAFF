<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

final readonly class CategoryPage
{
    public function __construct(
        public CategoryRef $category,
        public ?string $description,
        public ?string $seoTitle,
        public ?string $seoDescription,
    ) {}
}
