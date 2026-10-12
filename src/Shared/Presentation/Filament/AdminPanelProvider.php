<?php

declare(strict_types=1);

namespace Elaf\Shared\Presentation\Filament;

use Elaf\Content\Presentation\Filament\Resources\Articles\ArticleResource;
use Elaf\Content\Presentation\Filament\Resources\Authors\AuthorResource;
use Elaf\Content\Presentation\Filament\Resources\Categories\CategoryResource;
use Elaf\Content\Presentation\Filament\Resources\Series\SeriesResource;
use Elaf\Content\Presentation\Filament\Resources\Tags\TagResource;
use Elaf\Contributors\Presentation\Filament\Admin\Contributors\ContributorResource;
use Elaf\Engagement\Presentation\Filament\Letters\ReaderLetterResource;
use Elaf\Identity\Presentation\Filament\Resources\Users\UserResource;
use Elaf\Layout\Presentation\Filament\Ads\AdResource;
use Elaf\Layout\Presentation\Filament\Homepage\HomepageBlockResource;
use Elaf\Layout\Presentation\Filament\Menus\MenuResource;
use Elaf\Publishing\Presentation\Filament\Issues\IssueResource;
use Elaf\Settings\Presentation\Filament\ManageSettings;
use Elaf\Settings\Presentation\Filament\Pages\PageResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Newsroom admin at /admin (PRD §9). Arabic/RTL comes from the app locale.
 * Resources are listed explicitly: they live in each module's Presentation.
 */
final class AdminPanelProvider extends PanelProvider
{
    public const ID = 'admin';

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id(self::ID)
            ->path('admin')
            ->login()
            ->brandName(fn (): string => (string) __('site.name'))
            ->colors([
                'primary' => Color::hex('#0f4c5c'),
                'danger' => Color::hex('#b3261e'),
            ])
            ->resources([
                ArticleResource::class,
                IssueResource::class,
                ReaderLetterResource::class,
                ContributorResource::class,
                CategoryResource::class,
                TagResource::class,
                SeriesResource::class,
                AuthorResource::class,
                UserResource::class,
                PageResource::class,
                MenuResource::class,
                HomepageBlockResource::class,
                AdResource::class,
            ])
            ->pages([Dashboard::class, ManageSettings::class])
            ->widgets([AccountWidget::class])
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
