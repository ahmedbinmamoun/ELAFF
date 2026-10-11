<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

use DateTimeImmutable;

final readonly class ArticleDetail
{
    /**
     * @param  list<TagRef>  $tags
     */
    public function __construct(
        public ArticleCard $card,
        public ?string $subtitle,
        public ?string $dateline,
        public string $bodyHtml,
        public string $sourceName,
        public ?string $sourceUrl,
        public array $tags,
        public ?SeriesNavigation $series,
        public DateTimeImmutable $updatedAt,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public ?string $canonicalUrl,
    ) {}
}
