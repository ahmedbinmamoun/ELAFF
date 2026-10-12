<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad\Query;

interface AdQueries
{
    /**
     * Every active, not deleted ad, whatever its schedule (the caller checks
     * the time). One query; implementations may keep the result for the
     * request.
     *
     * @return list<AdCandidate>
     */
    public function active(): array;

    /**
     * Where a click on this ad leads; null when the ad is gone or has none.
     */
    public function target(int $adId): ?string;
}
