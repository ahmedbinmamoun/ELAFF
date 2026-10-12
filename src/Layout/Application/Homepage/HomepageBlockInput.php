<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage;

final readonly class HomepageBlockInput
{
    /**
     * @param  array<array-key, mixed>  $options
     */
    public function __construct(
        public string $type,
        public string $region,
        public array $options = [],
        public bool $isActive = true,
    ) {}
}
