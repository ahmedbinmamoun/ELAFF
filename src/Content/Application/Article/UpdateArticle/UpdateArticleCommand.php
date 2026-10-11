<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\UpdateArticle;

use Elaf\Content\Application\Article\ArticleInput;

final readonly class UpdateArticleCommand
{
    public function __construct(
        public int $actorId,
        public int $articleId,
        public ArticleInput $article,
    ) {}
}
