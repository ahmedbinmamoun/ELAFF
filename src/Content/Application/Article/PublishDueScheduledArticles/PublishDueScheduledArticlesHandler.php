<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\PublishDueScheduledArticles;

use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Application\Article\DueScheduledArticles;
use Elaf\Shared\Domain\Exception\DomainException;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-05, run by the scheduler every minute. There is no signed-in user:
 * the publication is attributed to whoever last acted on the article (the
 * person who scheduled it). One failing article does not block the others.
 */
final readonly class PublishDueScheduledArticlesHandler
{
    public function __construct(
        private DueScheduledArticles $due,
        private ArticleUnitOfWork $articles,
        private Clock $clock,
    ) {}

    public function handle(): PublishDueScheduledArticlesResult
    {
        $published = [];
        $failed = [];

        foreach ($this->due->dueAt($this->clock->now()) as $id) {
            $article = $this->articles->load($id);

            try {
                $article->publish($article->updatedBy(), $this->clock);
                $this->articles->persist($article);
                $published[] = $id;
            } catch (DomainException $e) {
                $failed[$id->value] = $e->getMessage();
            }
        }

        return new PublishDueScheduledArticlesResult($published, $failed);
    }
}
