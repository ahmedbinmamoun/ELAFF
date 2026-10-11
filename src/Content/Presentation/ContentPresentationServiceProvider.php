<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation;

use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Infrastructure\Persistence\Models\SeriesModel;
use Elaf\Content\Infrastructure\Persistence\Models\TagModel;
use Elaf\Content\Presentation\Http\PublicChrome;
use Elaf\Content\Presentation\Policies\ArticlePolicy;
use Elaf\Content\Presentation\Policies\AuthorPolicy;
use Elaf\Content\Presentation\Policies\CategoryPolicy;
use Elaf\Content\Presentation\Policies\SeriesPolicy;
use Elaf\Content\Presentation\Policies\TagPolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class ContentPresentationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(PublicChrome::class);
    }

    public function boot(Gate $gate): void
    {
        $gate->policy(ArticleModel::class, ArticlePolicy::class);
        $gate->policy(CategoryModel::class, CategoryPolicy::class);
        $gate->policy(TagModel::class, TagPolicy::class);
        $gate->policy(SeriesModel::class, SeriesPolicy::class);
        $gate->policy(AuthorModel::class, AuthorPolicy::class);

        Route::middleware('web')->group(__DIR__.'/Http/routes.php');

        View::composer(['layouts.app', 'homepage.*', 'search.*'], static function (\Illuminate\View\View $view): void {
            $view->with(app(PublicChrome::class)->data());
        });
    }
}
