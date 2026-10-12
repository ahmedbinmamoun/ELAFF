<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage\AddBlock;

use Elaf\Layout\Application\Homepage\HomepageBlockInput;

final readonly class AddHomepageBlockCommand
{
    public function __construct(
        public int $actorId,
        public HomepageBlockInput $block,
    ) {}
}
