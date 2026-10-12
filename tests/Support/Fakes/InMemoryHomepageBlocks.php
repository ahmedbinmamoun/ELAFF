<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Layout\Application\Homepage\BlockTypeCatalog;
use Elaf\Layout\Application\Homepage\HomepageBlock;
use Elaf\Layout\Application\Homepage\HomepageBlockData;
use Elaf\Layout\Application\Homepage\HomepageBlockQueries;
use Elaf\Layout\Application\Homepage\HomepageBlockRepository;

final class InMemoryHomepageBlocks implements BlockTypeCatalog, HomepageBlockQueries, HomepageBlockRepository
{
    /** @var array<int, HomepageBlockData> in display order */
    private array $blocks = [];

    private int $lastId = 0;

    /**
     * @param  list<string>  $types
     */
    public function __construct(private readonly array $types = ['hero', 'latest_articles', 'most_read']) {}

    public function knows(string $type): bool
    {
        return in_array($type, $this->types, true);
    }

    public function all(): array
    {
        $all = [];

        foreach ($this->blocks as $id => $data) {
            $all[] = new HomepageBlock($id, $data);
        }

        return $all;
    }

    public function active(): ?array
    {
        return $this->blocks === [] ? null : array_values(array_filter($this->blocks, static fn (HomepageBlockData $b): bool => $b->isActive));
    }

    public function find(int $id): ?HomepageBlock
    {
        return isset($this->blocks[$id]) ? new HomepageBlock($id, $this->blocks[$id]) : null;
    }

    public function add(HomepageBlockData $data): int
    {
        $this->blocks[++$this->lastId] = $data;

        return $this->lastId;
    }

    public function update(int $id, HomepageBlockData $data): void
    {
        $this->blocks[$id] = $data;
    }

    public function reorder(array $ids): void
    {
        $ordered = [];

        foreach ($ids as $id) {
            $ordered[$id] = $this->blocks[$id];
        }

        $this->blocks = $ordered;
    }

    public function remove(int $id): void
    {
        unset($this->blocks[$id]);
    }
}
