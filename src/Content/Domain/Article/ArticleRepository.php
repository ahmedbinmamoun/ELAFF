<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article;

use Elaf\Content\Domain\Article\Exception\ArticleNotFound;

/**
 * Write side only (PRD §5.5 I). Reads go through ArticleQueries.
 */
interface ArticleRepository
{
    public function nextId(): ArticleId;

    /**
     * @throws ArticleNotFound
     */
    public function get(ArticleId $id): Article;

    public function save(Article $article): void;
}
