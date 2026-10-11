<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Eloquent;

use Elaf\Content\Application\Tag\Tag;
use Elaf\Content\Application\Tag\TagQueries;
use Elaf\Content\Application\Tag\TagRepository;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Content\Infrastructure\Persistence\Models\TagModel;
use Elaf\Shared\Domain\Text\Slug;
use LogicException;

final class EloquentTagRepository implements TagQueries, TagRepository
{
    public function create(string $name, Slug $slug): TagId
    {
        return TagId::fromInt(TagModel::query()->create(['name' => $name, 'slug' => $slug->value])->id);
    }

    public function find(TagId $id): ?Tag
    {
        return $this->toDto(TagModel::query()->find($id->value));
    }

    public function findBySlug(Slug $slug): ?Tag
    {
        return $this->toDto(TagModel::query()->where('slug', $slug->value)->first());
    }

    public function suggest(string $prefix, int $limit = 10): array
    {
        $prefix = trim($prefix);

        if ($prefix === '') {
            return [];
        }

        return array_values(TagModel::query()
            ->whereRaw("name LIKE ? ESCAPE '!'", [str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $prefix).'%'])
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (TagModel $model): Tag => $this->toDto($model) ?? throw new LogicException)
            ->all());
    }

    private function toDto(?TagModel $model): ?Tag
    {
        return $model === null ? null : new Tag(TagId::fromInt($model->id), $model->name, Slug::fromString($model->slug));
    }
}
