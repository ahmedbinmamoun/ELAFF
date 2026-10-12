<?php

declare(strict_types=1);

namespace Tests\Support\Contract;

use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Domain\Issue\IssueId;

/**
 * Referenced records for repository contract tests: real rows for the
 * Eloquent implementations (foreign keys), plain ids for the in-memory ones.
 */
interface ContentFixtures
{
    public function user(): UserId;

    public function category(): CategoryId;

    public function author(): AuthorId;

    public function tag(): TagId;

    public function series(): SeriesId;

    public function issue(): IssueId;

    /**
     * Makes an article reference $id, for "in use" checks. $repository is
     * the repository under test (the in-memory one keeps its own flags).
     */
    public function useInArticle(object $repository, CategoryId|AuthorId|SeriesId $id): void;
}
