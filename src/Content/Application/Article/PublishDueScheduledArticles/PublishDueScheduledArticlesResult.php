<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\PublishDueScheduledArticles;

use Elaf\Content\Domain\Article\ArticleId;

final readonly class PublishDueScheduledArticlesResult
{
    /**
     * @param  list<ArticleId>  $published
     * @param  array<int, string>  $failed  article id => reason
     */
    public function __construct(
        public array $published,
        public array $failed,
    ) {}
}
