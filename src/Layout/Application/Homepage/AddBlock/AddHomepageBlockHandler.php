<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage\AddBlock;

use Elaf\Layout\Application\Homepage\HomepageBlocks;

/**
 * FR-HOME-01: a new block goes to the end of the list.
 */
final readonly class AddHomepageBlockHandler
{
    public function __construct(private HomepageBlocks $homepage) {}

    public function handle(AddHomepageBlockCommand $command): int
    {
        $this->homepage->requireManage();
        $data = $this->homepage->validate($command->block);
        $id = 0;

        $this->homepage->change($command->actorId, function () use ($data, &$id): void {
            $id = $this->homepage->blocks->add($data);
        });

        return $id;
    }
}
