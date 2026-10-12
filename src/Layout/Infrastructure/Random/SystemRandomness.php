<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure\Random;

use Elaf\Layout\Application\Ad\SelectForSlot\Randomness;

final class SystemRandomness implements Randomness
{
    public function upTo(int $max): int
    {
        return random_int(1, max(1, $max));
    }
}
