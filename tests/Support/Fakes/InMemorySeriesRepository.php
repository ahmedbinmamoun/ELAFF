<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Content\Application\Series\Series;
use Elaf\Content\Application\Series\SeriesData;
use Elaf\Content\Application\Series\SeriesRepository;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Shared\Domain\Text\Slug;
use RuntimeException;

final class InMemorySeriesRepository implements SeriesRepository
{
    /** @var array<int, SeriesData> */
    private array $rows = [];

    private int $lastId = 0;

    public function create(SeriesData $data): SeriesId
    {
        $id = ++$this->lastId;
        $this->rows[$id] = $data;

        return SeriesId::fromInt($id);
    }

    public function update(SeriesId $id, SeriesData $data): void
    {
        if (! isset($this->rows[$id->value])) {
            throw new RuntimeException("Series {$id} does not exist.");
        }

        $this->rows[$id->value] = $data;
    }

    public function find(SeriesId $id): ?Series
    {
        return isset($this->rows[$id->value]) ? new Series($id, $this->rows[$id->value]) : null;
    }

    public function slugExists(Slug $slug, ?SeriesId $except = null): bool
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
    public function markUsedByArticle(SeriesId $id): void
    {
        $this->usedByArticles[$id->value] = true;
    }

    public function hasArticles(SeriesId $id): bool
    {
        return isset($this->usedByArticles[$id->value]);
    }

    public function delete(SeriesId $id): void
    {
        unset($this->rows[$id->value]);
    }
}
