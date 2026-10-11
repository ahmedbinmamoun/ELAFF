<?php

declare(strict_types=1);

namespace Elaf\Settings\Infrastructure;

use Elaf\Content\Application\Article\EditorialSettings;
use Elaf\Contributors\Application\ContributorSettings;
use Illuminate\Support\ServiceProvider;

final class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConfigEditorialSettings::class);
        $this->app->alias(ConfigEditorialSettings::class, EditorialSettings::class);
        $this->app->alias(ConfigEditorialSettings::class, ContributorSettings::class);
    }
}
