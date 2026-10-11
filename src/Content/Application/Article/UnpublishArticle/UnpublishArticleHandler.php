<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\UnpublishArticle;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-04: published → unpublished. The URL keeps working for editors.
 */
final readonly class UnpublishArticleHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleAccess $access,
        private Clock $clock,
    ) {}

    public function handle(UnpublishArticleCommand $command): void
    {
        $actor = UserId::fromInt($command->actorId);
        $article = $this->articles->load(ArticleId::fromInt($command->articleId));

        $this->access->require(ContentPermission::PublishArticles);

        $article->unpublish($actor, $this->clock);
        $this->articles->persist($article);
    }
}
