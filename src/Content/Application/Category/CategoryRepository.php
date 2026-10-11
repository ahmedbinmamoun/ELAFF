<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Category;

use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Shared\Domain\Text\Slug;

interface CategoryRepository
{
    public function create(CategoryData $data): CategoryId;

    public function update(CategoryId $id, CategoryData $data): void;

    public function find(CategoryId $id): ?Category;

    public function slugExists(Slug $slug, ?CategoryId $except = null): bool;

    public function hasArticles(CategoryId $id): bool;

    public function hasChildren(CategoryId $id): bool;

    public function delete(CategoryId $id): void;
}
