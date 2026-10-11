<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\RestoreArticleRevision;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Application\Article\Revision\ArticleRevisions;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\Article;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-08: puts an earlier snapshot back. The restore is itself a new
 * revision, so it can be undone the same way.
 */
final readonly class RestoreArticleRevisionHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleRevisions $revisions,
        private ArticleAccess $access,
        private Clock $clock,
    ) {}

    public function handle(RestoreArticleRevisionCommand $command): void
    {
        $this->access->require(ContentPermission::EditAnyArticle);
        $actor = UserId::fromInt($command->actorId);
        $revision = $this->revisions->get($command->revisionId);
        $article = $this->articles->load($revision->articleId);

        if ($article->content()->equals($revision->content)) {
            return;
        }

        $article->revise($revision->content, $actor, $this->clock);
        $this->articles->persist($article, fn (Article $saved) => $this->revisions->record(
            $saved->id(), $actor, $saved->content(), $this->clock->now(),
        ));
    }
}
