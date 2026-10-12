<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage\ReorderBlocks;

use Elaf\Layout\Application\Homepage\HomepageBlock;
use Elaf\Layout\Application\Homepage\HomepageBlocks;
use Elaf\Shared\Application\Validation\InvalidInput;

/**
 * FR-HOME-01 drag and drop: the new order must name every block once, so a
 * stale screen cannot drop or duplicate blocks.
 */
final readonly class ReorderHomepageBlocksHandler
{
    public function __construct(private HomepageBlocks $homepage) {}

    public function handle(ReorderHomepageBlocksCommand $command): void
    {
        $this->homepage->requireManage();
        $current = array_map(static fn (HomepageBlock $b): int => $b->id, $this->homepage->blocks->all());
        $wanted = $command->blockIds;
        sort($current);
        sort($wanted);

        if ($current !== $wanted) {
            throw InvalidInput::because('blocks', 'stale_block_order', 'The block list changed meanwhile; reload the page.');
        }

        $this->homepage->change($command->actorId, fn () => $this->homepage->blocks->reorder($command->blockIds));
    }
}
