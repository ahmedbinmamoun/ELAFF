<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage\ReorderBlocks;

final readonly class ReorderHomepageBlocksCommand
{
    /**
     * @param  list<int>  $blockIds  every block, in the new order
     */
    public function __construct(
        public int $actorId,
        public array $blockIds,
    ) {}
}
