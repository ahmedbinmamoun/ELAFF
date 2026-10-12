<?php

declare(strict_types=1);

namespace Elaf\Settings\Presentation\Http;

use Elaf\Settings\Application\Page\Query\PageQueries;
use Illuminate\Contracts\View\View;

/**
 * FR-PAG-01: /page/{slug}.
 */
final readonly class PageController
{
    public function __construct(private PageQueries $pages) {}

    public function show(string $slug): View
    {
        return view('settings.page', ['page' => $this->pages->published($slug) ?? abort(404)]);
    }
}
