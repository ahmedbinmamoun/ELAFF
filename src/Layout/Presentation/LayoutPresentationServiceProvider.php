<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation;

use Elaf\Layout\Presentation\Homepage\Blocks\CategorySectionBlock;
use Elaf\Layout\Presentation\Homepage\Blocks\ColumnistsBlock;
use Elaf\Layout\Presentation\Homepage\Blocks\CurrentIssueBlock;
use Elaf\Layout\Presentation\Homepage\Blocks\HeroBlock;
use Elaf\Layout\Presentation\Homepage\Blocks\LatestArticlesBlock;
use Elaf\Layout\Presentation\Homepage\Blocks\MostReadBlock;
use Elaf\Layout\Presentation\Homepage\HomeController;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRegistry;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class LayoutPresentationServiceProvider extends ServiceProvider
{
    /**
     * Homepage block types (FR-HOME-02). Add a type here.
     *
     * @var list<class-string<Homepage\HomepageBlockRenderer>>
     */
    public const BLOCKS = [
        HeroBlock::class,
        LatestArticlesBlock::class,
        MostReadBlock::class,
        CategorySectionBlock::class,
        CurrentIssueBlock::class,
        ColumnistsBlock::class,
    ];

    public function register(): void
    {
        $this->app->singleton(HomepageBlockRegistry::class, static fn (Application $app): HomepageBlockRegistry => new HomepageBlockRegistry(
            array_map(static fn (string $class): Homepage\HomepageBlockRenderer => $app->make($class), self::BLOCKS),
        ));
    }

    public function boot(): void
    {
        Route::middleware('web')->get('/', HomeController::class)->name('home');
    }
}
