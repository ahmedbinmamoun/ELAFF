<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Http\Controllers;

use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Content\Application\Query\TaxonomyQueries;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class CategoryController
{
    public function __construct(
        private ArticleQueries $articles,
        private TaxonomyQueries $taxonomy,
    ) {}

    public function show(Request $request, string $slug): View
    {
        $category = $this->taxonomy->category($slug) ?? abort(404);
        $articles = $this->articles->byCategory($category->category->id, PublicPage::page($request), PublicPage::PER_PAGE);
        PublicPage::abortPastEnd($articles);

        return view('content.category', ['category' => $category, 'articles' => $articles, 'mostRead' => $this->articles->mostRead(PublicPage::MOST_READ)]);
    }
}
