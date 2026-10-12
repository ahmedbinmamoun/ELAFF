<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage;

use Elaf\Layout\Domain\Homepage\BlockRegion;

/**
 * One homepage block (FR-HOME-01). Lightweight model, PRD §6.5: the type's
 * renderer gives the options their meaning.
 */
final readonly class HomepageBlockData
{
    /**
     * @param  array<string, string|int|bool|null>  $options
     */
    public function __construct(
        public string $type,
        public BlockRegion $region,
        public array $options = [],
        public bool $isActive = true,
    ) {}
}
