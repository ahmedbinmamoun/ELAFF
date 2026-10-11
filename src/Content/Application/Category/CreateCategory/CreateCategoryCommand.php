<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Category\CreateCategory;

use Elaf\Content\Application\Category\CategoryInput;

final readonly class CreateCategoryCommand
{
    public function __construct(public CategoryInput $category) {}
}
