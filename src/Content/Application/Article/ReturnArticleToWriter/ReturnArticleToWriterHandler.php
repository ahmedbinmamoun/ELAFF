<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\ReturnArticleToWriter;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-04 / FR-CON-07: in_review → returned, with a mandatory note.
 */
final readonly class ReturnArticleToWriterHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleAccess $access,
        private Clock $clock,
    ) {}

    public function handle(ReturnArticleToWriterCommand $command): void
    {
        $this->access->require(ContentPermission::ReviewArticles);
        $article = $this->articles->load(ArticleId::fromInt($command->articleId));

        $article->returnToWriter(UserId::fromInt($command->actorId), $command->note, $this->clock);
        $this->articles->persist($article);
    }
}
