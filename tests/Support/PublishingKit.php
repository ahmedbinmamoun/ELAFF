<?php

declare(strict_types=1);

namespace Tests\Support;

use Elaf\Publishing\Application\Issue\IssueInput;
use Elaf\Publishing\Application\Issue\IssueUploader;
use Elaf\Publishing\Application\IssuePermission;
use Elaf\Publishing\Application\Port\ImageOptimizer;
use Elaf\Publishing\Application\Port\IssueArticleLinks;
use Elaf\Publishing\Application\Port\IssueDownloads;
use Elaf\Publishing\Application\Port\IssueFiles;
use Elaf\Publishing\Application\Port\PdfRasterizer;
use Elaf\Publishing\Application\Process\ProcessIssuePdfCommand;
use Elaf\Publishing\Application\Process\ProcessIssuePdfHandler;
use Elaf\Publishing\Domain\Issue\Issue;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Domain\Issue\IssueRepository;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Domain\Time\Clock;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;
use Tests\Support\Fakes\FakeImageOptimizer;
use Tests\Support\Fakes\FakeIssueFiles;
use Tests\Support\Fakes\FakePdfRasterizer;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuthorizer;
use Tests\Support\Fakes\InMemoryIssueCounters;
use Tests\Support\Fakes\InMemoryIssueRepository;
use Tests\Support\Fakes\RecordingEventDispatcher;

/**
 * Publishing use cases wired to in-memory fakes, no framework.
 */
final class PublishingKit
{
    public const EDITOR = 7;

    public readonly FrozenClock $clock;

    public readonly InMemoryAuthorizer $authorizer;

    public readonly RecordingEventDispatcher $events;

    public readonly InMemoryIssueRepository $issues;

    public readonly InMemoryIssueCounters $counters;

    public readonly FakeIssueFiles $files;

    public readonly FakePdfRasterizer $rasterizer;

    public readonly FakeImageOptimizer $images;

    /** @var array<class-string, object> */
    private array $bindings;

    public function __construct()
    {
        $this->clock = new FrozenClock('2026-10-08 10:00:00');
        $this->authorizer = new InMemoryAuthorizer([IssuePermission::ManageIssues->value]);
        $this->events = new RecordingEventDispatcher;
        $this->issues = new InMemoryIssueRepository;
        $this->counters = new InMemoryIssueCounters;
        $this->files = new FakeIssueFiles;
        $this->rasterizer = new FakePdfRasterizer;
        $this->images = new FakeImageOptimizer;

        $this->bindings = [
            Clock::class => $this->clock,
            Authorizer::class => $this->authorizer,
            EventDispatcher::class => $this->events,
            Transaction::class => new ImmediateTransaction,
            IssueRepository::class => $this->issues,
            IssueArticleLinks::class => $this->counters,
            IssueDownloads::class => $this->counters,
            IssueFiles::class => $this->files,
            PdfRasterizer::class => $this->rasterizer,
            ImageOptimizer::class => $this->images,
        ];
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T
     */
    public function make(string $class): object
    {
        $bound = $this->bindings[$class] ?? null;

        if ($bound instanceof $class) {
            return $bound;
        }

        $reflection = new ReflectionClass($class);
        $arguments = [];

        foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new LogicException("Cannot resolve \${$parameter->getName()} of {$class}.");
            }

            /** @var class-string $dependency */
            $dependency = $type->getName();
            $arguments[] = $this->make($dependency);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * A pending issue uploaded from a fake PDF whose content is $content.
     */
    public function pending(int $number = 120, string $date = '2026-10-08', string $content = 'pdf'): Issue
    {
        $id = $this->make(IssueUploader::class)->upload(
            new IssueInput($number, $date),
            $this->files->pdf("/tmp/upload-{$number}-{$content}.pdf", $content),
            null,
        );

        return $this->issues->get($id);
    }

    public function ready(int $number = 120, int $pages = 3): Issue
    {
        $issue = $this->pending($number);
        $this->rasterizer->pages = $pages;
        $this->make(ProcessIssuePdfHandler::class)->handle(new ProcessIssuePdfCommand($issue->id()->value));

        return $this->issues->get($issue->id());
    }

    public function get(IssueId $id): Issue
    {
        return $this->issues->get($id);
    }
}
