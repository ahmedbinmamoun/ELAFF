<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Eloquent;

use Elaf\Content\Application\Category\Category;
use Elaf\Content\Application\Category\CategoryData;
use Elaf\Content\Application\Category\CategoryRepository;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Shared\Domain\Text\Slug;

final class EloquentCategoryRepository implements CategoryRepository
{
    public function create(CategoryData $data): CategoryId
    {
        $model = CategoryModel::query()->create($this->columns($data));

        return CategoryId::fromInt($model->id);
    }

    public function update(CategoryId $id, CategoryData $data): void
    {
        CategoryModel::query()->whereKey($id->value)->firstOrFail()->update($this->columns($data));
    }

    public function find(CategoryId $id): ?Category
    {
        $model = CategoryModel::query()->find($id->value);

        return $model === null ? null : new Category(CategoryId::fromInt($model->id), new CategoryData(
            name: $model->name,
            slug: Slug::fromString($model->slug),
            description: $model->description,
            parentId: $model->parent_id === null ? null : CategoryId::fromInt($model->parent_id),
            sortOrder: $model->sort_order,
            isVisible: $model->is_visible,
            isOpenToContributors: $model->is_open_to_contributors,
            seoTitle: $model->seo_title,
            seoDescription: $model->seo_description,
            icon: $model->icon,
        ));
    }

    public function slugExists(Slug $slug, ?CategoryId $except = null): bool
    {
        return CategoryModel::query()
            ->where('slug', $slug->value)
            ->when($except !== null, static fn ($q) => $q->whereKeyNot($except?->value))
            ->exists();
    }

    public function hasArticles(CategoryId $id): bool
    {
        return ArticleModel::withTrashed()->where('category_id', $id->value)->exists();
    }

    public function hasChildren(CategoryId $id): bool
    {
        return CategoryModel::query()->where('parent_id', $id->value)->exists();
    }

    public function delete(CategoryId $id): void
    {
        CategoryModel::query()->whereKey($id->value)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function columns(CategoryData $data): array
    {
        return [
            'name' => $data->name,
            'slug' => $data->slug->value,
            'description' => $data->description,
            'parent_id' => $data->parentId?->value,
            'sort_order' => $data->sortOrder,
            'is_visible' => $data->isVisible,
            'is_open_to_contributors' => $data->isOpenToContributors,
            'seo_title' => $data->seoTitle,
            'seo_description' => $data->seoDescription,
            'icon' => $data->icon,
        ];
    }
}
