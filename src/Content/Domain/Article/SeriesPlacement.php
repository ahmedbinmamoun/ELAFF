<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article;

use Elaf\Content\Domain\Article\Exception\InvalidArticleContent;
use Elaf\Content\Domain\Series\SeriesId;

/**
 * Membership in a multi-part series (FR-TAX-03), e.g. part 3 of "كيف نفهم الحرب والسلام".
 */
final readonly class SeriesPlacement
{
    public function __construct(
        public SeriesId $seriesId,
        public int $part,
    ) {
        if ($part < 1) {
            throw InvalidArticleContent::notPositive('series part', $part);
        }
    }
}
