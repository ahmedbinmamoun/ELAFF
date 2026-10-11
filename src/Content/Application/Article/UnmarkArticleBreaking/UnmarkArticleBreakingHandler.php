<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\UnmarkArticleBreaking;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-06: removes the article from the breaking ticker.
 */
final readonly class UnmarkArticleBreakingHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleAccess $access,
        private Clock $clock,
    ) {}

    public function handle(UnmarkArticleBreakingCommand $command): void
    {
        $actor = UserId::fromInt($command->actorId);
        $article = $this->articles->load(ArticleId::fromInt($command->articleId));

        $this->access->require(ContentPermission::PromoteArticles);

        $article->unmarkBreaking($actor, $this->clock);
        $this->articles->persist($article);
    }
}
