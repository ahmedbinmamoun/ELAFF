<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Series\CreateSeries;

use Elaf\Content\Application\Series\SeriesInput;

final readonly class CreateSeriesCommand
{
    public function __construct(public SeriesInput $series) {}
}
