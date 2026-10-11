<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article;

use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleRepository;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;

/**
 * Load → change → save → dispatch, the shape shared by every article use
 * case (PRD §5.6).
 */
final readonly class ArticleUnitOfWork
{
    public function __construct(
        private ArticleRepository $articles,
        private Transaction $transaction,
        private EventDispatcher $events,
    ) {}

    public function load(ArticleId $id): Article
    {
        return $this->articles->get($id);
    }

    public function nextId(): ArticleId
    {
        return $this->articles->nextId();
    }

    /**
     * @param  (callable(Article): void)|null  $afterSave  more writes in the same transaction
     */
    public function persist(Article $article, ?callable $afterSave = null): void
    {
        $this->transaction->run(function () use ($article, $afterSave): void {
            $this->articles->save($article);

            if ($afterSave !== null) {
                $afterSave($article);
            }

            $this->events->dispatch(...$article->releaseEvents());
        });
    }
}
