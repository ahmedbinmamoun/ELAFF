<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Ads;

use Elaf\Layout\Application\Ad\Query\AdQueries;
use Elaf\Layout\Application\Ad\RecordClick\RecordAdClickHandler;
use Illuminate\Http\RedirectResponse;

/**
 * FR-ADS-03: /ads/{id}/go counts the click and sends the reader on.
 */
final readonly class AdClickController
{
    public function __construct(
        private AdQueries $ads,
        private RecordAdClickHandler $clicks,
    ) {}

    public function __invoke(int $id): RedirectResponse
    {
        $target = $this->ads->target($id) ?? abort(404);
        $this->clicks->handle($id);

        return redirect()->away($target);
    }
}
