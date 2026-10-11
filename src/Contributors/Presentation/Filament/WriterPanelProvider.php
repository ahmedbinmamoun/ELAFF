<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Filament;

use Elaf\Contributors\Presentation\Filament\Auth\MyProfile;
use Elaf\Contributors\Presentation\Filament\Auth\RegisterContributor;
use Elaf\Contributors\Presentation\Filament\Pages\WriterDashboard;
use Elaf\Contributors\Presentation\Filament\Resources\MyArticles\MyArticleResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Contributor portal at /writer (FR-CON-05): its own login and registration,
 * and only My Articles, New Article and My Profile.
 */
final class WriterPanelProvider extends PanelProvider
{
    public const ID = 'writer';

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id(self::ID)
            ->path('writer')
            ->login()
            ->registration(RegisterContributor::class)
            ->passwordReset()
            ->profile(MyProfile::class, isSimple: false)
            ->brandName(fn (): string => __('site.name').' — '.__('contributors.portal'))
            ->colors(['primary' => Color::hex('#0f4c5c')])
            ->resources([MyArticleResource::class])
            ->pages([WriterDashboard::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
