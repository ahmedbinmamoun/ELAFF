<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad\SelectForSlot;

/**
 * Randomness behind a port (PRD §5.5 D), so rotation is testable.
 */
interface Randomness
{
    /**
     * An integer from 1 to $max inclusive.
     */
    public function upTo(int $max): int;
}
