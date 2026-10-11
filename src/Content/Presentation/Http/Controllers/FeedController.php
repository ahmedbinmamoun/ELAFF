<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Http\Controllers;

use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Content\Application\Query\TaxonomyQueries;
use Illuminate\Http\Response;

/**
 * RSS 2.0 for the latest articles, site-wide or per category (FR-SEO-03).
 */
final readonly class FeedController
{
    private const ITEMS = 30;

    public function __construct(
        private ArticleQueries $articles,
        private TaxonomyQueries $taxonomy,
    ) {}

    public function __invoke(?string $category = null): Response
    {
        $page = $category === null ? null : ($this->taxonomy->category($category) ?? abort(404));
        $items = $page === null
            ? $this->articles->latest(self::ITEMS)
            : $this->articles->byCategory($page->category->id, 1, self::ITEMS)->items;

        return response()
            ->view('content.feed', ['category' => $page, 'items' => $items])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
