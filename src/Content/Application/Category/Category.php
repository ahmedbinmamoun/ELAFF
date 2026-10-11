<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Category;

use Elaf\Content\Domain\Category\CategoryId;

final readonly class Category
{
    public function __construct(
        public CategoryId $id,
        public CategoryData $data,
    ) {}
}
