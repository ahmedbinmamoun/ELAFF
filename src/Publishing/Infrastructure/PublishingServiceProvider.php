<?php

declare(strict_types=1);

namespace Elaf\Publishing\Infrastructure;

use Elaf\Content\Application\Article\PrintIssues;
use Elaf\Publishing\Application\Port\ImageOptimizer;
use Elaf\Publishing\Application\Port\IssueArticleLinks;
use Elaf\Publishing\Application\Port\IssueDownloads;
use Elaf\Publishing\Application\Port\IssueFiles;
use Elaf\Publishing\Application\Port\PdfRasterizer;
use Elaf\Publishing\Application\Query\IssueQueries;
use Elaf\Publishing\Domain\Issue\Events\IssueProcessingRetried;
use Elaf\Publishing\Domain\Issue\Events\IssueUploaded;
use Elaf\Publishing\Domain\Issue\IssueRepository;
use Elaf\Publishing\Infrastructure\Content\PublishingPrintIssues;
use Elaf\Publishing\Infrastructure\Files\DiskIssueFiles;
use Elaf\Publishing\Infrastructure\Persistence\EloquentIssueCounters;
use Elaf\Publishing\Infrastructure\Persistence\EloquentIssueRepository;
use Elaf\Publishing\Infrastructure\Processing\ImagickImageOptimizer;
use Elaf\Publishing\Infrastructure\Processing\PopplerPdfRasterizer;
use Elaf\Publishing\Infrastructure\Processing\QueueIssueProcessing;
use Elaf\Publishing\Infrastructure\Query\EloquentIssueQueries;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem as LocalFiles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

final class PublishingServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        IssueRepository::class => EloquentIssueRepository::class,
        IssueQueries::class => EloquentIssueQueries::class,
        EloquentIssueCounters::class => EloquentIssueCounters::class,
        PrintIssues::class => PublishingPrintIssues::class,
    ];

    public function register(): void
    {
        $this->app->alias(EloquentIssueCounters::class, IssueArticleLinks::class);
        $this->app->alias(EloquentIssueCounters::class, IssueDownloads::class);

        $this->app->singleton(IssueFiles::class, static fn (): IssueFiles => new DiskIssueFiles(self::disk()));
        $this->app->singleton(ImageOptimizer::class, static fn (): ImageOptimizer => new ImagickImageOptimizer(self::disk()));
        $this->app->singleton(PdfRasterizer::class, static fn (Application $app): PdfRasterizer => new PopplerPdfRasterizer(
            self::disk(),
            $app->make(LocalFiles::class),
            $app->make(LoggerInterface::class),
        ));
    }

    public function boot(Dispatcher $events): void
    {
        $events->listen([IssueUploaded::class, IssueProcessingRetried::class], QueueIssueProcessing::class);
    }

    /**
     * Where issue PDFs and page images live (config elaf.issues.disk).
     */
    public static function disk(): Filesystem
    {
        $disk = config('elaf.issues.disk', 'public');

        return Storage::disk(is_string($disk) ? $disk : 'public');
    }
}
