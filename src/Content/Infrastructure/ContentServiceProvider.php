<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure;

use Elaf\Content\Application\Article\ArticleCounts;
use Elaf\Content\Application\Article\DueScheduledArticles;
use Elaf\Content\Application\Article\Revision\ArticleRevisions;
use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Application\Category\CategoryRepository;
use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Content\Application\Query\TaxonomyQueries;
use Elaf\Content\Application\Series\SeriesRepository;
use Elaf\Content\Application\Tag\TagQueries;
use Elaf\Content\Application\Tag\TagRepository;
use Elaf\Content\Domain\Article\ArticleRepository;
use Elaf\Content\Domain\Article\Events\ArticleApproved;
use Elaf\Content\Domain\Article\Events\ArticleAssignedToIssue;
use Elaf\Content\Domain\Article\Events\ArticleBreakingCleared;
use Elaf\Content\Domain\Article\Events\ArticleCreated;
use Elaf\Content\Domain\Article\Events\ArticleMarkedBreaking;
use Elaf\Content\Domain\Article\Events\ArticlePromoted;
use Elaf\Content\Domain\Article\Events\ArticlePublished;
use Elaf\Content\Domain\Article\Events\ArticleReturnedToWriter;
use Elaf\Content\Domain\Article\Events\ArticleScheduled;
use Elaf\Content\Domain\Article\Events\ArticleSubmissionWithdrawn;
use Elaf\Content\Domain\Article\Events\ArticleSubmittedForReview;
use Elaf\Content\Domain\Article\Events\ArticleUnpublished;
use Elaf\Content\Domain\Article\Events\ArticleUpdated;
use Elaf\Content\Infrastructure\Audit\RecordArticleActivity;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentArticleRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentArticleRevisions;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentAuthorRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentSeriesRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentTagRepository;
use Elaf\Content\Infrastructure\Query\EloquentArticleQueries;
use Elaf\Content\Infrastructure\Query\EloquentTaxonomyQueries;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class ContentServiceProvider extends ServiceProvider
{
    /**
     * Every article event goes to the audit log (FR-ART-08).
     */
    private const AUDITED_EVENTS = [
        ArticleCreated::class,
        ArticleUpdated::class,
        ArticleSubmittedForReview::class,
        ArticleSubmissionWithdrawn::class,
        ArticleApproved::class,
        ArticleReturnedToWriter::class,
        ArticleScheduled::class,
        ArticlePublished::class,
        ArticleUnpublished::class,
        ArticleMarkedBreaking::class,
        ArticleBreakingCleared::class,
        ArticlePromoted::class,
        ArticleAssignedToIssue::class,
    ];

    /** @var array<class-string, class-string> */
    public array $singletons = [
        EloquentArticleRepository::class => EloquentArticleRepository::class,
        EloquentTagRepository::class => EloquentTagRepository::class,
        CategoryRepository::class => EloquentCategoryRepository::class,
        SeriesRepository::class => EloquentSeriesRepository::class,
        AuthorRepository::class => EloquentAuthorRepository::class,
        ArticleRevisions::class => EloquentArticleRevisions::class,
        ArticleQueries::class => EloquentArticleQueries::class,
        TaxonomyQueries::class => EloquentTaxonomyQueries::class,
    ];

    public function register(): void
    {
        $this->app->alias(EloquentArticleRepository::class, ArticleRepository::class);
        $this->app->alias(EloquentArticleRepository::class, DueScheduledArticles::class);
        $this->app->alias(EloquentArticleRepository::class, ArticleCounts::class);
        $this->app->alias(EloquentTagRepository::class, TagRepository::class);
        $this->app->alias(EloquentTagRepository::class, TagQueries::class);
    }

    public function boot(Dispatcher $events): void
    {
        foreach (self::AUDITED_EVENTS as $event) {
            $events->listen($event, RecordArticleActivity::class);
        }
    }
}
