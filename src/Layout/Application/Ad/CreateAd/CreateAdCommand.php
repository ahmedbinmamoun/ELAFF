<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad\CreateAd;

use Elaf\Layout\Application\Ad\AdInput;

final readonly class CreateAdCommand
{
    public function __construct(
        public int $actorId,
        public AdInput $ad,
    ) {}
}
