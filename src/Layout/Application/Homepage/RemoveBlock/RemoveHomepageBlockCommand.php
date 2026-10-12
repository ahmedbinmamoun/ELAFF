<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage\RemoveBlock;

final readonly class RemoveHomepageBlockCommand
{
    public function __construct(
        public int $actorId,
        public int $blockId,
    ) {}
}
