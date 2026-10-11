<?php

declare(strict_types=1);

namespace Tests\Support\Contract;

use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Identity\Domain\UserId;
use LogicException;

final class InMemoryContentFixtures implements ContentFixtures
{
    private int $next = 0;

    public function user(): UserId
    {
        return UserId::fromInt(++$this->next);
    }

    public function category(): CategoryId
    {
        return CategoryId::fromInt(++$this->next);
    }

    public function author(): AuthorId
    {
        return AuthorId::fromInt(++$this->next);
    }

    public function tag(): TagId
    {
        return TagId::fromInt(++$this->next);
    }

    public function series(): SeriesId
    {
        return SeriesId::fromInt(++$this->next);
    }

    public function useInArticle(object $repository, CategoryId|AuthorId|SeriesId $id): void
    {
        if (! method_exists($repository, 'markUsedByArticle')) {
            throw new LogicException($repository::class.' cannot be marked as used.');
        }

        $repository->markUsedByArticle($id);
    }
}
