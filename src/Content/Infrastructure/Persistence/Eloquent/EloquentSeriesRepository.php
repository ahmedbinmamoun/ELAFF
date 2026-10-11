<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Eloquent;

use Elaf\Content\Application\Series\Series;
use Elaf\Content\Application\Series\SeriesData;
use Elaf\Content\Application\Series\SeriesRepository;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Content\Infrastructure\Persistence\Models\SeriesModel;
use Elaf\Shared\Domain\Text\Slug;

final class EloquentSeriesRepository implements SeriesRepository
{
    public function create(SeriesData $data): SeriesId
    {
        return SeriesId::fromInt(SeriesModel::query()->create($this->columns($data))->id);
    }

    public function update(SeriesId $id, SeriesData $data): void
    {
        SeriesModel::query()->whereKey($id->value)->firstOrFail()->update($this->columns($data));
    }

    public function find(SeriesId $id): ?Series
    {
        $model = SeriesModel::query()->find($id->value);

        return $model === null ? null : new Series(
            SeriesId::fromInt($model->id),
            new SeriesData($model->title, Slug::fromString($model->slug), $model->description),
        );
    }

    public function slugExists(Slug $slug, ?SeriesId $except = null): bool
    {
        return SeriesModel::query()
            ->where('slug', $slug->value)
            ->when($except !== null, static fn ($q) => $q->whereKeyNot($except?->value))
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function columns(SeriesData $data): array
    {
        return ['title' => $data->title, 'slug' => $data->slug->value, 'description' => $data->description];
    }
}
