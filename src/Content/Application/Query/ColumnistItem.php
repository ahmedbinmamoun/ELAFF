<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

use DateTimeImmutable;

/**
 * FR-AUT-02 homepage strip: a columnist and their latest column.
 */
final readonly class ColumnistItem
{
    public function __construct(
        public AuthorRef $author,
        public ArticleLink $latest,
        public DateTimeImmutable $publishedAt,
    ) {}
}
