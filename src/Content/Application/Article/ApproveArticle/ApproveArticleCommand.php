<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\ApproveArticle;

final readonly class ApproveArticleCommand
{
    public function __construct(
        public int $actorId,
        public int $articleId,
    ) {}
}
