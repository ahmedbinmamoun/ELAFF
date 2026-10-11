<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation;

use Elaf\Contributors\Infrastructure\Mail\ContributorMailer;
use Elaf\Contributors\Infrastructure\Persistence\ContributorProfileModel;
use Elaf\Contributors\Presentation\Http\VerifyEmailController;
use Elaf\Contributors\Presentation\Policies\ContributorProfilePolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class ContributorsPresentationServiceProvider extends ServiceProvider
{
    public function boot(Gate $gate): void
    {
        $gate->policy(ContributorProfileModel::class, ContributorProfilePolicy::class);

        Route::middleware('web')->group(static function (): void {
            Route::redirect('/writers/register', '/writer/register');

            Route::get('/writer/verify-email/{id}/{hash}', VerifyEmailController::class)
                ->whereNumber('id')
                ->middleware(['signed', 'throttle:6,1'])
                ->name(ContributorMailer::VERIFY_ROUTE);
        });
    }
}
