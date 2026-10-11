<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Http\Controllers;

use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Content\Application\Query\TaxonomyQueries;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class TagController
{
    public function __construct(
        private ArticleQueries $articles,
        private TaxonomyQueries $taxonomy,
    ) {}

    public function show(Request $request, string $slug): View
    {
        $tag = $this->taxonomy->tag($slug) ?? abort(404);
        $articles = $this->articles->byTag($tag->slug, PublicPage::page($request), PublicPage::PER_PAGE);
        PublicPage::abortPastEnd($articles);

        return view('content.tag', ['tag' => $tag, 'articles' => $articles, 'mostRead' => $this->articles->mostRead(PublicPage::MOST_READ)]);
    }
}
