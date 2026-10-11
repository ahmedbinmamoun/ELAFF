<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\SubmitArticleForReview;

final readonly class SubmitArticleForReviewCommand
{
    public function __construct(
        public int $actorId,
        public int $articleId,
    ) {}
}
