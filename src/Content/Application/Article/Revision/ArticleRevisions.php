<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\Revision;

use DateTimeImmutable;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Validation\RecordNotFound;

/**
 * Content snapshots taken on every save (FR-ART-08).
 */
interface ArticleRevisions
{
    public function record(ArticleId $articleId, UserId $userId, ArticleContent $content, DateTimeImmutable $at): void;

    /**
     * @throws RecordNotFound
     */
    public function get(int $revisionId): ArticleRevision;

    /**
     * @return list<ArticleRevision> newest first
     */
    public function forArticle(ArticleId $articleId): array;
}
