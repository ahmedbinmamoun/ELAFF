<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Category\UpdateCategory;

use Elaf\Content\Application\Category\CategoryInput;

final readonly class UpdateCategoryCommand
{
    public function __construct(
        public int $categoryId,
        public CategoryInput $category,
    ) {}
}
