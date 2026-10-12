<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad\RecordClick;

use Elaf\Layout\Application\Ad\AdClicks;
use Elaf\Layout\Domain\Ad\AdId;

/**
 * FR-ADS-03: /ads/{id}/go counts, then redirects. Impressions are not
 * tracked in v1.
 */
final readonly class RecordAdClickHandler
{
    public function __construct(private AdClicks $clicks) {}

    public function handle(int $adId): void
    {
        $this->clicks->record(AdId::fromInt($adId));
    }
}
