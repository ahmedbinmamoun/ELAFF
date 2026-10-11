<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\UpdateArticle;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleContentFactory;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Application\Article\ContributorByline;
use Elaf\Content\Application\Article\Revision\ArticleRevisions;
use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Domain\Time\Clock;

/**
 * Saves editor changes; every effective change becomes a revision (FR-ART-08).
 */
final readonly class UpdateArticleHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleContentFactory $contentFactory,
        private ArticleRevisions $revisions,
        private ArticleAccess $access,
        private ContributorByline $byline,
        private Transaction $transaction,
        private Clock $clock,
    ) {}

    public function handle(UpdateArticleCommand $command): void
    {
        $actor = UserId::fromInt($command->actorId);
        $article = $this->articles->load(ArticleId::fromInt($command->articleId));
        $this->access->requireCanEdit($article, $actor);

        $this->transaction->run(function () use ($command, $article, $actor): void {
            $previous = $article->content();
            $input = $this->byline->apply($command->article, $article->origin(), $article->createdBy(), $actor);
            $article->revise($this->contentFactory->create($input, $article->origin()), $actor, $this->clock);

            if ($article->content()->equals($previous)) {
                return;
            }

            $this->articles->persist($article, fn (Article $saved) => $this->revisions->record(
                $saved->id(), $actor, $saved->content(), $this->clock->now(),
            ));
        });
    }
}
