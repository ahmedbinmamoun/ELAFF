<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Series;

use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Shared\Domain\Text\Slug;

interface SeriesRepository
{
    public function create(SeriesData $data): SeriesId;

    public function update(SeriesId $id, SeriesData $data): void;

    public function find(SeriesId $id): ?Series;

    public function slugExists(Slug $slug, ?SeriesId $except = null): bool;

    public function hasArticles(SeriesId $id): bool;

    public function delete(SeriesId $id): void;
}
