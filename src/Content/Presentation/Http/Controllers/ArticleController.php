<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Http\Controllers;

use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Contributors\Application\ContributorSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final readonly class ArticleController
{
    public function __construct(
        private ArticleQueries $articles,
        private ContributorSettings $settings,
    ) {}

    /**
     * FR-ART-10: the id finds the article; a missing or outdated slug is
     * redirected (301) to the canonical URL.
     */
    public function show(int $id, ?string $slug = null): View|RedirectResponse
    {
        $article = $this->articles->detail($id) ?? abort(404);

        if ($slug !== $article->card->slug) {
            return redirect()->route('article.show', ['id' => $id, 'slug' => $article->card->slug], 301);
        }

        return view('content.article', [
            'article' => $article,
            'related' => $this->articles->related($id, $article->card->category->id, 4),
            'guestLabel' => $article->card->byContributor && $this->settings->showGuestBylineLabel(),
            'mostRead' => $this->articles->mostRead(PublicPage::MOST_READ),
        ]);
    }
}
