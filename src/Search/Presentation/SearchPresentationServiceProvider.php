<?php

declare(strict_types=1);

namespace Elaf\Search\Presentation;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Search route. Until Phase 6 builds search (FR-SRC-01/02), /search shows a
 * styled placeholder echoing the query.
 */
final class SearchPresentationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(static function (): void {
            Route::view('/search', 'search.soon')->name('search');
        });
    }
}
