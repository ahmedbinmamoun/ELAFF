<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad\DeleteAd;

final readonly class DeleteAdCommand
{
    public function __construct(
        public int $actorId,
        public int $adId,
    ) {}
}
