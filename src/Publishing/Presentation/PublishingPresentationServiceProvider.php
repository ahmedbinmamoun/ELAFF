<?php

declare(strict_types=1);

namespace Elaf\Publishing\Presentation;

use Elaf\Publishing\Infrastructure\Persistence\IssueModel;
use Elaf\Publishing\Presentation\Http\IssueController;
use Elaf\Publishing\Presentation\Policies\IssuePolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Issues archive (FR-ISS-03, 04; PRD §9) and its admin policy.
 */
final class PublishingPresentationServiceProvider extends ServiceProvider
{
    public function boot(Gate $gate): void
    {
        $gate->policy(IssueModel::class, IssuePolicy::class);

        Route::middleware('web')->group(static function (): void {
            Route::get('/issues', [IssueController::class, 'index'])->name('issues.index');
            Route::get('/issues/{number}', [IssueController::class, 'show'])->whereNumber('number')->name('issues.show');
            Route::get('/issues/{number}/pdf', [IssueController::class, 'pdf'])->whereNumber('number')
                ->middleware('throttle:30,1')->name('issues.pdf');
        });
    }
}
