<?php

declare(strict_types=1);

namespace Elaf\Settings\Presentation;

use Elaf\Settings\Infrastructure\Persistence\PageModel;
use Elaf\Settings\Presentation\Http\PageController;
use Elaf\Settings\Presentation\Policies\PagePolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class SettingsPresentationServiceProvider extends ServiceProvider
{
    public function boot(Gate $gate): void
    {
        $gate->policy(PageModel::class, PagePolicy::class);

        Route::middleware('web')->group(static function (): void {
            Route::get('/page/{slug}', [PageController::class, 'show'])->name('page.show');
        });
    }
}
