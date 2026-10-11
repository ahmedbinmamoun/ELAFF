<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Series;

use Elaf\Content\Domain\Series\SeriesId;

final readonly class Series
{
    public function __construct(
        public SeriesId $id,
        public SeriesData $data,
    ) {}
}
