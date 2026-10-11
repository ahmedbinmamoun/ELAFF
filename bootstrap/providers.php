<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Elaf\Content\Infrastructure\ContentServiceProvider;
use Elaf\Content\Presentation\ContentPresentationServiceProvider;
use Elaf\Identity\Infrastructure\IdentityServiceProvider;
use Elaf\Identity\Presentation\IdentityPresentationServiceProvider;
use Elaf\Shared\Infrastructure\SharedServiceProvider;
use Elaf\Shared\Presentation\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    ContentServiceProvider::class,
    IdentityServiceProvider::class,
    ContentPresentationServiceProvider::class,
    IdentityPresentationServiceProvider::class,
    AdminPanelProvider::class,
];
