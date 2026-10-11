<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\UnpublishArticle;

final readonly class UnpublishArticleCommand
{
    public function __construct(
        public int $actorId,
        public int $articleId,
    ) {}
}
