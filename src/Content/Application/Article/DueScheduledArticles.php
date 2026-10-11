<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article;

use DateTimeImmutable;
use Elaf\Content\Domain\Article\ArticleId;

interface DueScheduledArticles
{
    /**
     * Scheduled articles whose publication time is at or before $now, oldest first.
     *
     * @return list<ArticleId>
     */
    public function dueAt(DateTimeImmutable $now): array;
}
