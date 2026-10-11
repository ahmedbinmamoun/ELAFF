<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\ScheduleArticle;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-04/05: approved → scheduled; also reschedules a scheduled article.
 */
final readonly class ScheduleArticleHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleAccess $access,
        private Clock $clock,
    ) {}

    public function handle(ScheduleArticleCommand $command): void
    {
        $article = $this->articles->load(ArticleId::fromInt($command->articleId));
        $this->access->requireCanPublish($article);

        $article->schedule($command->publishAt, UserId::fromInt($command->actorId), $this->clock);
        $this->articles->persist($article);
    }
}
