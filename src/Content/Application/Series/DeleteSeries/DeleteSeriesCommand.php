<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Series\DeleteSeries;

final readonly class DeleteSeriesCommand
{
    public function __construct(public int $seriesId) {}
}
