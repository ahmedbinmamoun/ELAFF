<?php

declare(strict_types=1);

namespace Elaf\Settings\Infrastructure;

use Elaf\Content\Application\Article\EditorialSettings;
use Elaf\Contributors\Application\ContributorSettings;
use Elaf\Settings\Application\Page\PageRepository;
use Elaf\Settings\Application\Page\Query\PageQueries;
use Elaf\Settings\Application\Site\SettingsReader;
use Elaf\Settings\Application\Site\SettingsStore;
use Elaf\Settings\Domain\Events\SettingsChanged;
use Elaf\Settings\Infrastructure\Persistence\EloquentPageQueries;
use Elaf\Settings\Infrastructure\Persistence\EloquentPageRepository;
use Elaf\Settings\Infrastructure\Persistence\EloquentSettingsStore;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class SettingsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        SettingsStore::class => EloquentSettingsStore::class,
        PageRepository::class => EloquentPageRepository::class,
        PageQueries::class => EloquentPageQueries::class,
        StoredEditorialSettings::class => StoredEditorialSettings::class,
    ];

    public function register(): void
    {
        // Scoped: one read per request (or queued job), dropped afterwards.
        $this->app->scoped(CachedSettingsReader::class);
        $this->app->alias(CachedSettingsReader::class, SettingsReader::class);
        $this->app->alias(StoredEditorialSettings::class, EditorialSettings::class);
        $this->app->alias(StoredEditorialSettings::class, ContributorSettings::class);
    }

    public function boot(Dispatcher $events): void
    {
        $events->listen(SettingsChanged::class, function (): void {
            $this->app->make(CachedSettingsReader::class)->forget();
        });
    }
}
