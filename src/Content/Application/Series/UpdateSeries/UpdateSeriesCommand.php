<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Series\UpdateSeries;

use Elaf\Content\Application\Series\SeriesInput;

final readonly class UpdateSeriesCommand
{
    public function __construct(
        public int $seriesId,
        public SeriesInput $series,
    ) {}
}
