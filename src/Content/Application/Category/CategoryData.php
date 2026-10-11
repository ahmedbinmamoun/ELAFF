<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Category;

use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Shared\Domain\Text\Slug;

/**
 * Validated category fields (FR-TAX-01). Lightweight model, PRD §6.5.
 */
final readonly class CategoryData
{
    public function __construct(
        public string $name,
        public Slug $slug,
        public ?string $description = null,
        public ?CategoryId $parentId = null,
        public int $sortOrder = 0,
        public bool $isVisible = true,
        public bool $isOpenToContributors = false,
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
        public ?string $icon = null,
    ) {}
}
