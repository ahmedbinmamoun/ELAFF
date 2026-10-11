<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\PromoteArticle;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-07: featured flag and homepage priority for the hero block.
 */
final readonly class PromoteArticleHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleAccess $access,
        private Clock $clock,
    ) {}

    public function handle(PromoteArticleCommand $command): void
    {
        $this->access->require(ContentPermission::PromoteArticles);
        $article = $this->articles->load(ArticleId::fromInt($command->articleId));

        $article->promote($command->featured, $command->homepagePriority, UserId::fromInt($command->actorId), $this->clock);
        $this->articles->persist($article);
    }
}
