<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use DateTimeImmutable;
use Elaf\Content\Application\Article\DueScheduledArticles;
use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleRepository;
use Elaf\Content\Domain\Article\Exception\ArticleNotFound;

/**
 * Stores copies, like a database would: changing an aggregate without
 * save() is not visible to the next get().
 */
final class InMemoryArticleRepository implements ArticleRepository, DueScheduledArticles
{
    /** @var array<int, Article> */
    private array $articles = [];

    private int $lastId = 0;

    public int $saves = 0;

    public function nextId(): ArticleId
    {
        return ArticleId::fromInt(++$this->lastId);
    }

    public function get(ArticleId $id): Article
    {
        if (! isset($this->articles[$id->value])) {
            throw ArticleNotFound::withId($id);
        }

        return clone $this->articles[$id->value];
    }

    public function save(Article $article): void
    {
        $copy = clone $article;
        $copy->releaseEvents();
        $this->articles[$article->id()->value] = $copy;
        $this->saves++;
    }

    public function dueAt(DateTimeImmutable $now): array
    {
        $due = array_filter($this->articles, static fn (Article $a): bool => $a->isDueForPublication($now));
        usort($due, static fn (Article $a, Article $b): int => [$a->scheduledFor(), $a->id()->value] <=> [$b->scheduledFor(), $b->id()->value]);

        return array_map(static fn (Article $a): ArticleId => $a->id(), $due);
    }
}
