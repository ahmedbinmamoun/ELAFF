<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Http\Controllers;

use Elaf\Content\Application\Query\TaxonomyQueries;
use Illuminate\Contracts\View\View;

/**
 * FR-TAX-03: a series and its parts in order.
 */
final readonly class SeriesController
{
    public function __construct(private TaxonomyQueries $taxonomy) {}

    public function show(string $slug): View
    {
        return view('content.series', ['series' => $this->taxonomy->series($slug) ?? abort(404)]);
    }
}
