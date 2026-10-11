<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\CreateArticleDraft;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleContentFactory;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Application\Article\Revision\ArticleRevisions;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Domain\Time\Clock;

final readonly class CreateArticleDraftHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleContentFactory $contentFactory,
        private ArticleRevisions $revisions,
        private ArticleAccess $access,
        private Transaction $transaction,
        private Clock $clock,
    ) {}

    public function handle(CreateArticleDraftCommand $command): ArticleId
    {
        $this->access->require(ContentPermission::CreateArticles);
        $actor = UserId::fromInt($command->actorId);

        return $this->transaction->run(function () use ($command, $actor): ArticleId {
            $content = $this->contentFactory->create($command->article, $command->origin);
            $article = Article::draft($this->articles->nextId(), $command->origin, $actor, $content, $this->clock);

            $this->articles->persist($article, fn (Article $saved) => $this->revisions->record(
                $saved->id(), $actor, $saved->content(), $this->clock->now(),
            ));

            return $article->id();
        });
    }
}
