<?php

declare(strict_types=1);

namespace Elaf\Settings\Infrastructure\Persistence;

use Elaf\Settings\Application\Page\Page;
use Elaf\Settings\Application\Page\PageData;
use Elaf\Settings\Application\Page\PageRepository;
use Elaf\Settings\Domain\Page\PageId;
use Elaf\Shared\Domain\Text\Slug;

final class EloquentPageRepository implements PageRepository
{
    public function create(PageData $data): PageId
    {
        return PageId::fromInt(PageModel::query()->create($this->columns($data))->id);
    }

    public function update(PageId $id, PageData $data): void
    {
        PageModel::query()->findOrFail($id->value)->fill($this->columns($data))->save();
    }

    public function find(PageId $id): ?Page
    {
        $model = PageModel::query()->find($id->value);

        return $model === null ? null : new Page($id, new PageData(
            $model->title, Slug::fromString($model->slug), $model->body, $model->is_published, $model->seo_title, $model->seo_description,
        ));
    }

    public function slugExists(Slug $slug, ?PageId $except = null): bool
    {
        return PageModel::query()
            ->where('slug', $slug->value)
            ->when($except !== null, static fn ($q) => $q->whereKeyNot($except?->value))
            ->exists();
    }

    public function delete(PageId $id): void
    {
        PageModel::query()->whereKey($id->value)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function columns(PageData $data): array
    {
        return [
            'title' => $data->title,
            'slug' => $data->slug->value,
            'body' => $data->body,
            'is_published' => $data->isPublished,
            'seo_title' => $data->seoTitle,
            'seo_description' => $data->seoDescription,
        ];
    }
}
