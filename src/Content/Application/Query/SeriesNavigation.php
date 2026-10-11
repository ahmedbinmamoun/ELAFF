<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

/**
 * FR-TAX-03: where an article sits in its series.
 */
final readonly class SeriesNavigation
{
    public function __construct(
        public string $title,
        public string $slug,
        public int $part,
        public int $parts,
        public ?ArticleLink $previous,
        public ?ArticleLink $next,
    ) {}
}
