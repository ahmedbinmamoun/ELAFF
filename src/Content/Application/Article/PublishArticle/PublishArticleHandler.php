<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\PublishArticle;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-04: approved, scheduled or unpublished → published.
 */
final readonly class PublishArticleHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleAccess $access,
        private Clock $clock,
    ) {}

    public function handle(PublishArticleCommand $command): void
    {
        $actor = UserId::fromInt($command->actorId);
        $article = $this->articles->load(ArticleId::fromInt($command->articleId));

        $this->access->requireCanPublish($article);

        $article->publish($actor, $this->clock);
        $this->articles->persist($article);
    }
}
