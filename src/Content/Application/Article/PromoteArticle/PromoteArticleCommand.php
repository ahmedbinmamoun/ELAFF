<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\PromoteArticle;

final readonly class PromoteArticleCommand
{
    public function __construct(
        public int $actorId,
        public int $articleId,
        public bool $featured,
        public int $homepagePriority = 0,
    ) {}
}
