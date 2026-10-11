<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\ApproveArticle;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-04: in_review → approved. Editor or higher, never the writer.
 */
final readonly class ApproveArticleHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleAccess $access,
        private Clock $clock,
    ) {}

    public function handle(ApproveArticleCommand $command): void
    {
        $actor = UserId::fromInt($command->actorId);
        $article = $this->articles->load(ArticleId::fromInt($command->articleId));

        $this->access->require(ContentPermission::ReviewArticles);

        $article->approve($actor, $this->clock);
        $this->articles->persist($article);
    }
}
