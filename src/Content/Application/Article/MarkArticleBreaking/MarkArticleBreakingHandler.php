<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\MarkArticleBreaking;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-06: puts the article in the breaking ticker, optionally until a time.
 */
final readonly class MarkArticleBreakingHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleAccess $access,
        private Clock $clock,
    ) {}

    public function handle(MarkArticleBreakingCommand $command): void
    {
        $this->access->require(ContentPermission::PromoteArticles);
        $article = $this->articles->load(ArticleId::fromInt($command->articleId));

        $article->markBreaking($command->until, UserId::fromInt($command->actorId), $this->clock);
        $this->articles->persist($article);
    }
}
