<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Http\Controllers;

use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Content\Application\Query\TaxonomyQueries;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * FR-AUT-02: author pages and the columnists index.
 */
final readonly class AuthorController
{
    public function __construct(
        private ArticleQueries $articles,
        private TaxonomyQueries $taxonomy,
    ) {}

    public function index(): View
    {
        return view('content.authors', ['columnists' => $this->articles->columnists(100)]);
    }

    public function show(Request $request, string $slug): View
    {
        $author = $this->taxonomy->author($slug) ?? abort(404);
        $articles = $this->articles->byAuthor($author->id, PublicPage::page($request), PublicPage::PER_PAGE);
        PublicPage::abortPastEnd($articles);

        return view('content.author', ['author' => $author, 'articles' => $articles, 'mostRead' => $this->articles->mostRead(PublicPage::MOST_READ)]);
    }
}
