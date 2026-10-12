<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage\ConfigureBlock;

use Elaf\Layout\Application\Homepage\HomepageBlockInput;

final readonly class ConfigureHomepageBlockCommand
{
    public function __construct(
        public int $actorId,
        public int $blockId,
        public HomepageBlockInput $block,
    ) {}
}
