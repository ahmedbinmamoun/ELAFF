<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\PublishArticle;

final readonly class PublishArticleCommand
{
    public function __construct(
        public int $actorId,
        public int $articleId,
    ) {}
}
