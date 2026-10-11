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

    private int $lastId = 0;

    public function create(CategoryData $data): CategoryId
    {
        $id = ++$this->lastId;
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

    /** @var array<int, true> */
    private array $usedByArticles = [];

    /**
     * Test setup: pretend an article references this record.
     */
    public function markUsedByArticle(CategoryId $id): void
    {
        $this->usedByArticles[$id->value] = true;
    }

    public function hasArticles(CategoryId $id): bool
    {
        return isset($this->usedByArticles[$id->value]);
    }

    public function hasChildren(CategoryId $id): bool
    {
        foreach ($this->rows as $data) {
            if ($data->parentId?->equals($id) === true) {
                return true;
            }
        }

        return false;
    }

    public function delete(CategoryId $id): void
    {
        unset($this->rows[$id->value]);
    }
}
