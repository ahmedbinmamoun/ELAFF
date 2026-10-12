<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad\UpdateAd;

use Elaf\Layout\Application\Ad\AdInput;

final readonly class UpdateAdCommand
{
    public function __construct(
        public int $actorId,
        public int $adId,
        public AdInput $ad,
    ) {}
}
