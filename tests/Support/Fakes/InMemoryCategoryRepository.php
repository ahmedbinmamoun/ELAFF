<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Content\Application\Category\Category;
use Elaf\Content\Application\Category\CategoryData;
use Elaf\Content\Application\Category\CategoryRepository;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Shared\Domain\Text\Slug;
use RuntimeException;

final class InMemoryCategoryRepository implements CategoryRepository
{
    /** @var array<int, CategoryData> */
    private array $rows = [];

    public function create(CategoryData $data): CategoryId
    {
        $id = count($this->rows) + 1;
        $this->rows[$id] = $data;

        return CategoryId::fromInt($id);
    }

    public function update(CategoryId $id, CategoryData $data): void
    {
        if (! isset($this->rows[$id->value])) {
            throw new RuntimeException("Category {$id} does not exist.");
        }

        $this->rows[$id->value] = $data;
    }

    public function find(CategoryId $id): ?Category
    {
        return isset($this->rows[$id->value]) ? new Category($id, $this->rows[$id->value]) : null;
    }

    public function slugExists(Slug $slug, ?CategoryId $except = null): bool
    {
        foreach ($this->rows as $id => $data) {
            if ($data->slug->equals($slug) && $id !== $except?->value) {
                return true;
            }
        }

        return false;
    }
}
