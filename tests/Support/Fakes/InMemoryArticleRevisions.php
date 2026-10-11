<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use DateTimeImmutable;
use Elaf\Content\Application\Article\Revision\ArticleRevision;
use Elaf\Content\Application\Article\Revision\ArticleRevisions;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Validation\RecordNotFound;

final class InMemoryArticleRevisions implements ArticleRevisions
{
    /** @var array<int, ArticleRevision> */
    private array $revisions = [];

    public function record(ArticleId $articleId, UserId $userId, ArticleContent $content, DateTimeImmutable $at): void
    {
        $id = count($this->revisions) + 1;
        $this->revisions[$id] = new ArticleRevision($id, $articleId, $userId, $at, $content);
    }

    public function get(int $revisionId): ArticleRevision
    {
        return $this->revisions[$revisionId] ?? throw RecordNotFound::of('Article revision', $revisionId);
    }

    public function forArticle(ArticleId $articleId): array
    {
        $mine = array_filter($this->revisions, static fn (ArticleRevision $r): bool => $r->articleId->equals($articleId));
        krsort($mine);

        return array_values($mine);
    }
}
