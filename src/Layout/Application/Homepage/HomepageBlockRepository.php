<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage;

interface HomepageBlockRepository
{
    /**
     * Every block, active or not, in display order.
     *
     * @return list<HomepageBlock>
     */
    public function all(): array;

    public function find(int $id): ?HomepageBlock;

    /**
     * Appends the block at the end; returns its id.
     */
    public function add(HomepageBlockData $data): int;

    public function update(int $id, HomepageBlockData $data): void;

    /**
     * @param  list<int>  $ids  every block id, in the new order
     */
    public function reorder(array $ids): void;

    public function remove(int $id): void;
}
