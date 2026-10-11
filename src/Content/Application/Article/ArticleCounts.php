<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article;

use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;

/**
 * Per-writer figures the contributor rules need (FR-CON-10, FR-CON-12).
 */
interface ArticleCounts
{
    public function inReviewBy(UserId $writer): int;

    /**
     * @return list<ArticleId>
     */
    public function publishedBy(UserId $writer): array;
}
