<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Elaf\Content\Infrastructure\ContentServiceProvider;
use Elaf\Content\Presentation\ContentPresentationServiceProvider;
use Elaf\Contributors\Infrastructure\ContributorsServiceProvider;
use Elaf\Contributors\Presentation\ContributorsPresentationServiceProvider;
use Elaf\Contributors\Presentation\Filament\WriterPanelProvider;
use Elaf\Engagement\Infrastructure\EngagementServiceProvider;
use Elaf\Engagement\Presentation\EngagementPresentationServiceProvider;
use Elaf\Identity\Infrastructure\IdentityServiceProvider;
use Elaf\Identity\Presentation\IdentityPresentationServiceProvider;
use Elaf\Layout\Infrastructure\LayoutServiceProvider;
use Elaf\Layout\Presentation\LayoutPresentationServiceProvider;
use Elaf\Publishing\Infrastructure\PublishingServiceProvider;
use Elaf\Publishing\Presentation\PublishingPresentationServiceProvider;
use Elaf\Search\Presentation\SearchPresentationServiceProvider;
use Elaf\Settings\Infrastructure\SettingsServiceProvider;
use Elaf\Settings\Presentation\SettingsPresentationServiceProvider;
use Elaf\Shared\Infrastructure\SharedServiceProvider;
use Elaf\Shared\Presentation\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    SettingsServiceProvider::class,
    ContentServiceProvider::class,
    IdentityServiceProvider::class,
    ContributorsServiceProvider::class,
    PublishingServiceProvider::class,
    LayoutServiceProvider::class,
    EngagementServiceProvider::class,
    ContentPresentationServiceProvider::class,
    IdentityPresentationServiceProvider::class,
    ContributorsPresentationServiceProvider::class,
    LayoutPresentationServiceProvider::class,
    PublishingPresentationServiceProvider::class,
    SearchPresentationServiceProvider::class,
    SettingsPresentationServiceProvider::class,
    EngagementPresentationServiceProvider::class,
    AdminPanelProvider::class,
    WriterPanelProvider::class,
];
