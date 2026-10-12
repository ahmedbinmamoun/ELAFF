<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation;

use Elaf\Layout\Application\Homepage\BlockTypeCatalog;
use Elaf\Layout\Infrastructure\Persistence\HomepageBlockModel;
use Elaf\Layout\Infrastructure\Persistence\MenuModel;
use Elaf\Layout\Presentation\Homepage\Blocks\BreakingTickerBlock;
use Elaf\Layout\Presentation\Homepage\Blocks\CategorySectionBlock;
use Elaf\Layout\Presentation\Homepage\Blocks\ColumnistsBlock;
use Elaf\Layout\Presentation\Homepage\Blocks\CurrentIssueBlock;
use Elaf\Layout\Presentation\Homepage\Blocks\HeroBlock;
use Elaf\Layout\Presentation\Homepage\Blocks\LatestArticlesBlock;
use Elaf\Layout\Presentation\Homepage\Blocks\MostReadBlock;
use Elaf\Layout\Presentation\Homepage\HomeController;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRegistry;
use Elaf\Layout\Presentation\Menus\LinkTypes\CategoryLink;
use Elaf\Layout\Presentation\Menus\LinkTypes\IssuesLink;
use Elaf\Layout\Presentation\Menus\LinkTypes\PageLink;
use Elaf\Layout\Presentation\Menus\LinkTypes\UrlLink;
use Elaf\Layout\Presentation\Menus\MenuLinkRegistry;
use Elaf\Layout\Presentation\Menus\PublicMenus;
use Elaf\Layout\Presentation\Policies\HomepageBlockPolicy;
use Elaf\Layout\Presentation\Policies\MenuPolicy;
use Illuminate\Contracts\Auth\Access\Gate;
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
        BreakingTickerBlock::class,
        HeroBlock::class,
        LatestArticlesBlock::class,
        MostReadBlock::class,
        CategorySectionBlock::class,
        CurrentIssueBlock::class,
        ColumnistsBlock::class,
    ];

    /**
     * What menu items can link to (FR-NAV-01). Add a link type here.
     *
     * @var list<class-string<Menus\MenuLinkType>>
     */
    public const MENU_LINKS = [CategoryLink::class, PageLink::class, IssuesLink::class, UrlLink::class];

    public function register(): void
    {
        $this->app->singleton(HomepageBlockRegistry::class, static fn (Application $app): HomepageBlockRegistry => new HomepageBlockRegistry(
            array_map(static fn (string $class): Homepage\HomepageBlockRenderer => $app->make($class), self::BLOCKS),
        ));
        $this->app->alias(HomepageBlockRegistry::class, BlockTypeCatalog::class);
        $this->app->singleton(MenuLinkRegistry::class, static fn (Application $app): MenuLinkRegistry => new MenuLinkRegistry(
            array_map(static fn (string $class): Menus\MenuLinkType => $app->make($class), self::MENU_LINKS),
        ));
        $this->app->scoped(PublicMenus::class);
    }

    public function boot(Gate $gate): void
    {
        $gate->policy(MenuModel::class, MenuPolicy::class);
        $gate->policy(HomepageBlockModel::class, HomepageBlockPolicy::class);

        Route::middleware('web')->get('/', HomeController::class)->name('home');
    }
}
