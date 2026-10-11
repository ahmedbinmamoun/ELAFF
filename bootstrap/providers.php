<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Elaf\Content\Infrastructure\ContentServiceProvider;
use Elaf\Content\Presentation\ContentPresentationServiceProvider;
use Elaf\Contributors\Infrastructure\ContributorsServiceProvider;
use Elaf\Contributors\Presentation\ContributorsPresentationServiceProvider;
use Elaf\Contributors\Presentation\Filament\WriterPanelProvider;
use Elaf\Identity\Infrastructure\IdentityServiceProvider;
use Elaf\Identity\Presentation\IdentityPresentationServiceProvider;
use Elaf\Settings\Infrastructure\SettingsServiceProvider;
use Elaf\Shared\Infrastructure\SharedServiceProvider;
use Elaf\Shared\Presentation\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    SettingsServiceProvider::class,
    ContentServiceProvider::class,
    IdentityServiceProvider::class,
    ContributorsServiceProvider::class,
    ContentPresentationServiceProvider::class,
    IdentityPresentationServiceProvider::class,
    ContributorsPresentationServiceProvider::class,
    AdminPanelProvider::class,
    WriterPanelProvider::class,
];
