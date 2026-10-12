<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure;

use Elaf\Layout\Application\Homepage\HomepageBlockQueries;
use Elaf\Layout\Application\Homepage\HomepageBlockRepository;
use Elaf\Layout\Application\Menu\MenuRepository;
use Elaf\Layout\Application\Menu\MenuTargets;
use Elaf\Layout\Application\Menu\Query\MenuQueries;
use Elaf\Layout\Infrastructure\Persistence\DatabaseMenuTargets;
use Elaf\Layout\Infrastructure\Persistence\EloquentHomepageBlocks;
use Elaf\Layout\Infrastructure\Persistence\EloquentMenuQueries;
use Elaf\Layout\Infrastructure\Persistence\EloquentMenuRepository;
use Illuminate\Support\ServiceProvider;

final class LayoutServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        MenuRepository::class => EloquentMenuRepository::class,
        MenuQueries::class => EloquentMenuQueries::class,
        MenuTargets::class => DatabaseMenuTargets::class,
        EloquentHomepageBlocks::class => EloquentHomepageBlocks::class,
    ];

    public function register(): void
    {
        $this->app->alias(EloquentHomepageBlocks::class, HomepageBlockRepository::class);
        $this->app->alias(EloquentHomepageBlocks::class, HomepageBlockQueries::class);
    }
}
