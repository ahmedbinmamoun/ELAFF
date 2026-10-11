<?php

declare(strict_types=1);

namespace Elaf\Publishing\Presentation;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Issues archive routes. Until Phase 4 builds the archive (FR-ISS-03/04),
 * /issues shows a styled "coming soon" page so the tab bar link works.
 */
final class PublishingPresentationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(static function (): void {
            Route::view('/issues', 'publishing.issues-soon')->name('issues.index');
        });
    }
}
