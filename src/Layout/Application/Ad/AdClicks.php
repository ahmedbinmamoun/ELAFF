<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad;

use Elaf\Layout\Domain\Ad\AdId;

/**
 * Click counter (FR-ADS-03), a read-model column, not aggregate state.
 */
interface AdClicks
{
    public function record(AdId $id): void;
}
