<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Category\DeleteCategory;

final readonly class DeleteCategoryCommand
{
    public function __construct(public int $categoryId) {}
}
