<?php

declare(strict_types=1);

namespace Elaf\Engagement\Presentation;

use Elaf\Engagement\Infrastructure\Persistence\ReaderLetterModel;
use Elaf\Engagement\Presentation\Http\LetterController;
use Elaf\Engagement\Presentation\Policies\ReaderLetterPolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * "بريد القراء" (FR-LET-01..03).
 */
final class EngagementPresentationServiceProvider extends ServiceProvider
{
    public function boot(Gate $gate): void
    {
        $gate->policy(ReaderLetterModel::class, ReaderLetterPolicy::class);

        Route::middleware('web')->group(static function (): void {
            Route::get('/letters', [LetterController::class, 'create'])->name('letters.create');
            Route::post('/letters', [LetterController::class, 'store'])->middleware('throttle:5,1')->name('letters.store');
        });
    }
}
