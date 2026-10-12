<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage;

final readonly class HomepageBlock
{
    public function __construct(
        public int $id,
        public HomepageBlockData $data,
    ) {}
}
