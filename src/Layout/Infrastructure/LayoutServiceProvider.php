<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure;

use Elaf\Layout\Application\Ad\AdClicks;
use Elaf\Layout\Application\Ad\Query\AdQueries;
use Elaf\Layout\Application\Ad\SelectForSlot\Randomness;
use Elaf\Layout\Application\Homepage\HomepageBlockQueries;
use Elaf\Layout\Application\Homepage\HomepageBlockRepository;
use Elaf\Layout\Application\Menu\MenuRepository;
use Elaf\Layout\Application\Menu\MenuTargets;
use Elaf\Layout\Application\Menu\Query\MenuQueries;
use Elaf\Layout\Domain\Ad\AdRepository;
use Elaf\Layout\Infrastructure\Persistence\DatabaseMenuTargets;
use Elaf\Layout\Infrastructure\Persistence\EloquentAdQueries;
use Elaf\Layout\Infrastructure\Persistence\EloquentAdRepository;
use Elaf\Layout\Infrastructure\Persistence\EloquentHomepageBlocks;
use Elaf\Layout\Infrastructure\Persistence\EloquentMenuQueries;
use Elaf\Layout\Infrastructure\Persistence\EloquentMenuRepository;
use Elaf\Layout\Infrastructure\Random\SystemRandomness;
use Illuminate\Support\ServiceProvider;

final class LayoutServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        MenuRepository::class => EloquentMenuRepository::class,
        MenuQueries::class => EloquentMenuQueries::class,
        MenuTargets::class => DatabaseMenuTargets::class,
        EloquentHomepageBlocks::class => EloquentHomepageBlocks::class,
        AdRepository::class => EloquentAdRepository::class,
        Randomness::class => SystemRandomness::class,
    ];

    public function register(): void
    {
        $this->app->alias(EloquentHomepageBlocks::class, HomepageBlockRepository::class);
        $this->app->alias(EloquentHomepageBlocks::class, HomepageBlockQueries::class);
        $this->app->scoped(EloquentAdQueries::class);
        $this->app->alias(EloquentAdQueries::class, AdQueries::class);
        $this->app->alias(EloquentAdQueries::class, AdClicks::class);
    }
}
