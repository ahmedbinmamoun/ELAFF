<?php

declare(strict_types=1);

namespace Elaf\Shared\Domain\Time;

use DateTimeImmutable;

/**
 * The only source of "now" for Domain and Application code.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
