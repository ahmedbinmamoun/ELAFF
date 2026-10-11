<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Category;

/**
 * Raw form values for creating or updating a category. A null slug is
 * derived from the name.
 */
final readonly class CategoryInput
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?string $description = null,
        public ?int $parentId = null,
        public int $sortOrder = 0,
        public bool $isVisible = true,
        public bool $isOpenToContributors = false,
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
        public ?string $icon = null,
    ) {}
}
