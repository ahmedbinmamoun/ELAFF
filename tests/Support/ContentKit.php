<?php

declare(strict_types=1);

namespace Tests\Support;

use Elaf\Content\Application\Article\ArticleInput;
use Elaf\Content\Application\Article\DueScheduledArticles;
use Elaf\Content\Application\Article\Revision\ArticleRevisions;
use Elaf\Content\Application\Author\AuthorData;
use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Application\Category\CategoryData;
use Elaf\Content\Application\Category\CategoryRepository;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Application\Series\SeriesData;
use Elaf\Content\Application\Series\SeriesRepository;
use Elaf\Content\Application\Tag\TagQueries;
use Elaf\Content\Application\Tag\TagRepository;
use Elaf\Content\Domain\Article\ArticleRepository;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Html\HtmlSanitizer;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Domain\Text\Slug;
use Elaf\Shared\Domain\Time\Clock;
use Elaf\Shared\Infrastructure\Html\HtmlPurifierSanitizer;
use LogicException;
use ReflectionClass;
use ReflectionNamedType;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryArticleRepository;
use Tests\Support\Fakes\InMemoryArticleRevisions;
use Tests\Support\Fakes\InMemoryAuthorizer;
use Tests\Support\Fakes\InMemoryAuthorRepository;
use Tests\Support\Fakes\InMemoryCategoryRepository;
use Tests\Support\Fakes\InMemorySeriesRepository;
use Tests\Support\Fakes\InMemoryTagRepository;
use Tests\Support\Fakes\RecordingEventDispatcher;

/**
 * Content use cases wired to in-memory fakes, no framework (PRD §14,
 * "Application" level). make() builds any handler from the fakes.
 */
final class ContentKit
{
    public readonly FrozenClock $clock;

    public readonly InMemoryAuthorizer $authorizer;

    public readonly RecordingEventDispatcher $events;

    public readonly ImmediateTransaction $transaction;

    public readonly InMemoryArticleRepository $articles;

    public readonly InMemoryCategoryRepository $categories;

    public readonly InMemorySeriesRepository $series;

    public readonly InMemoryAuthorRepository $authors;

    public readonly InMemoryTagRepository $tags;

    public readonly InMemoryArticleRevisions $revisions;

    public readonly HtmlSanitizer $sanitizer;

    /** @var array<class-string, object> */
    private array $bindings;

    public function __construct()
    {
        $this->clock = new FrozenClock('2026-03-05 10:00:00');
        $this->authorizer = new InMemoryAuthorizer;
        $this->events = new RecordingEventDispatcher;
        $this->transaction = new ImmediateTransaction;
        $this->articles = new InMemoryArticleRepository;
        $this->categories = new InMemoryCategoryRepository;
        $this->series = new InMemorySeriesRepository;
        $this->authors = new InMemoryAuthorRepository;
        $this->tags = new InMemoryTagRepository;
        $this->revisions = new InMemoryArticleRevisions;
        $this->sanitizer = new HtmlPurifierSanitizer;

        $this->bindings = [
            Clock::class => $this->clock,
            Authorizer::class => $this->authorizer,
            EventDispatcher::class => $this->events,
            Transaction::class => $this->transaction,
            ArticleRepository::class => $this->articles,
            DueScheduledArticles::class => $this->articles,
            CategoryRepository::class => $this->categories,
            SeriesRepository::class => $this->series,
            AuthorRepository::class => $this->authors,
            TagRepository::class => $this->tags,
            TagQueries::class => $this->tags,
            ArticleRevisions::class => $this->revisions,
            HtmlSanitizer::class => $this->sanitizer,
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

    public function grant(ContentPermission ...$permissions): self
    {
        foreach ($permissions as $permission) {
            $this->authorizer->grant($permission->value);
        }

        return $this;
    }

    public function category(string $name = 'أخبار', bool $openToContributors = false): int
    {
        return $this->categories->create(new CategoryData($name, Slug::fromText($name), isOpenToContributors: $openToContributors))->value;
    }

    public function author(string $name = 'كاتب تجريبي'): int
    {
        return $this->authors->create(new AuthorData($name, Slug::fromText($name)))->value;
    }

    public function aSeries(string $title = 'سلسلة تجريبية'): int
    {
        return $this->series->create(new SeriesData($title, Slug::fromText($title)))->value;
    }

    /**
     * A valid, publishable input, with a fresh category and author unless given.
     *
     * @param  array<string, mixed>  $overrides  ArticleInput named arguments
     */
    public function input(array $overrides = []): ArticleInput
    {
        return (new ReflectionClass(ArticleInput::class))->newInstanceArgs([
            'title' => 'عنوان المقال',
            'body' => '<p>نص المقال التجريبي</p>',
            'sourceName' => 'إيلاف',
            'categoryId' => $overrides['categoryId'] ?? $this->category('قسم '.uniqid()),
            'authorIds' => $overrides['authorIds'] ?? [$this->author('كاتب '.uniqid())],
            ...$overrides,
        ]);
    }
}
