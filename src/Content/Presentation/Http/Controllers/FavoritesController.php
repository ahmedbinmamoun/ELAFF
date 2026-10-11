<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Http\Controllers;

use Elaf\Content\Application\Query\ArticleQueries;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Reader favourites live in the browser (localStorage); the page asks for the
 * saved ids' cards. No account, nothing stored on the server.
 */
final readonly class FavoritesController
{
    public const MAX = 50;

    public function __construct(private ArticleQueries $articles) {}

    public function index(): View
    {
        return view('content.favorites');
    }

    /**
     * HTML fragment of list cards for ?ids=3,1,2 (order kept, unknown ids skipped).
     */
    public function cards(Request $request): View
    {
        $raw = $request->query('ids');
        $ids = is_string($raw) ? array_slice(array_map(intval(...), array_filter(explode(',', $raw), is_numeric(...))), 0, self::MAX) : [];

        return view('content.partials.favorite-cards', ['cards' => $this->articles->cards($ids)]);
    }
}
