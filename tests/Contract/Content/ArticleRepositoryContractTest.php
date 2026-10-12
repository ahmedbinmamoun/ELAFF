<?php

declare(strict_types=1);

use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleBody;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleRepository;
use Elaf\Content\Domain\Article\ArticleSource;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Article\Exception\ArticleNotFound;
use Elaf\Content\Domain\Article\FeaturedImage;
use Elaf\Content\Domain\Article\IssuePlacement;
use Elaf\Content\Domain\Article\SeriesPlacement;
use Elaf\Content\Domain\Article\Title;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentArticleRepository;
use Elaf\Shared\Domain\Text\Slug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Contract\ArticleState;
use Tests\Support\Contract\ContentFixtures;
use Tests\Support\Contract\EloquentContentFixtures;
use Tests\Support\Contract\InMemoryContentFixtures;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\InMemoryArticleRepository;

uses(RefreshDatabase::class);

dataset('article repositories', [
    'in-memory' => fn (): array => [new InMemoryArticleRepository, new InMemoryContentFixtures],
    'eloquent' => fn (): array => [app(EloquentArticleRepository::class), new EloquentContentFixtures],
]);

beforeEach(function (): void {
    $this->clock = new FrozenClock('2026-03-05 10:00:00');
});

/**
 * A fully populated article in the given state.
 */
function contract_article(ArticleRepository $repository, ContentFixtures $fx, FrozenClock $clock, ArticleStatus $until = ArticleStatus::Published): Article
{
    $writer = $fx->user();
    $editor = $fx->user();
    $content = new ArticleContent(
        title: new Title('عنوان العقد'),
        slug: Slug::fromText('عنوان العقد'),
        body: ArticleBody::fromSanitizedHtml('<p>نص</p><table><tr><td>1</td></tr></table>'),
        source: new ArticleSource('رويترز', 'https://example.com/a'),
        categoryId: $fx->category(),
        authorIds: [$fx->author(), $fx->author()],
        tagIds: [$fx->tag(), $fx->tag()],
        kicker: 'خاص',
        subtitle: 'عنوان فرعي',
        dateline: 'الخرطوم',
        excerpt: 'مقتطف',
        featuredImage: new FeaturedImage('articles/a.webp', 'تعليق', 'إيلاف'),
        series: new SeriesPlacement($fx->series(), 2),
        seoTitle: 'عنوان SEO',
        seoDescription: 'وصف',
        canonicalUrl: 'https://example.com/canonical',
        printSection: 'حوار',
    );

    $article = Article::draft($repository->nextId(), ArticleOrigin::Contributor, $writer, $content, $clock);
    $clock->advance('PT1H');

    if ($until !== ArticleStatus::Draft) {
        $article->submitForReview($writer, $clock);
        $clock->advance('PT1H');
        $article->approve($editor, $clock);
    }

    if ($until === ArticleStatus::Published) {
        $article->publish($editor, $clock);
        $article->markBreaking($clock->now()->modify('+6 hours'), $editor, $clock);
        $article->promote(true, 4, $editor, $clock);
        $article->assignToIssue(new IssuePlacement($fx->issue(), 3), $editor, $clock);
    }

    return $article;
}

it('hands out unique, increasing ids', function (ArticleRepository $repository, ContentFixtures $fx): void {
    $a = $repository->nextId();
    $b = $repository->nextId();

    expect($b->value)->toBeGreaterThan($a->value);
})->with('article repositories');

it('throws ArticleNotFound for an unknown id', function (ArticleRepository $repository, ContentFixtures $fx): void {
    $repository->get(ArticleId::fromInt(424242));
})->with('article repositories')->throws(ArticleNotFound::class);

it('returns exactly what was saved', function (ArticleRepository $repository, ContentFixtures $fx): void {
    $article = contract_article($repository, $fx, $this->clock);
    $repository->save($article);

    $loaded = $repository->get($article->id());

    expect(ArticleState::of($loaded))->toBe(ArticleState::of($article))
        ->and($loaded->releaseEvents())->toBe([]);
})->with('article repositories');

it('keeps the byline order the editor chose', function (ArticleRepository $repository, ContentFixtures $fx): void {
    $article = contract_article($repository, $fx, $this->clock, ArticleStatus::Draft);
    $authors = array_reverse($article->content()->authorIds);
    $c = $article->content();
    $article->revise(new ArticleContent($c->title, $c->slug, $c->body, $c->source, $c->categoryId, $authors, []), $article->createdBy(), $this->clock);
    $repository->save($article);

    $loaded = $repository->get($article->id())->content();

    expect($loaded->authorIds)->toEqual($authors)
        ->and($loaded->tagIds)->toBe([]);
})->with('article repositories');

it('overwrites on a second save', function (ArticleRepository $repository, ContentFixtures $fx): void {
    $article = contract_article($repository, $fx, $this->clock, ArticleStatus::Approved);
    $repository->save($article);

    $article->schedule($this->clock->now()->modify('+1 day'), $article->reviewedBy() ?? $article->createdBy(), $this->clock);
    $repository->save($article);

    expect(ArticleState::of($repository->get($article->id())))->toBe(ArticleState::of($article));
})->with('article repositories');

it('does not see changes that were never saved', function (ArticleRepository $repository, ContentFixtures $fx): void {
    $article = contract_article($repository, $fx, $this->clock, ArticleStatus::Draft);
    $repository->save($article);

    $copy = $repository->get($article->id());
    $copy->submitForReview($article->createdBy(), $this->clock);

    expect($repository->get($article->id())->status())->toBe(ArticleStatus::Draft);
})->with('article repositories');

it('lists due scheduled articles oldest first, whatever the timezone', function (ArticleRepository&Elaf\Content\Application\Article\DueScheduledArticles $repository, ContentFixtures $fx): void {
    $utc = new DateTimeZone('UTC');
    $chief = $fx->user();
    $schedule = function (string $at) use ($repository, $fx, $chief): ArticleId {
        $article = contract_article($repository, $fx, $this->clock, ArticleStatus::Approved);
        $article->schedule(new DateTimeImmutable($at, new DateTimeZone('UTC')), $chief, $this->clock);
        $repository->save($article);

        return $article->id();
    };

    $late = $schedule('2026-03-06 09:00:00');
    $early = $schedule('2026-03-06 08:00:00');
    $schedule('2026-03-06 09:00:01');

    expect($repository->dueAt(new DateTimeImmutable('2026-03-06 09:00:00', $utc)))->toEqual([$early, $late])
        ->and($repository->dueAt(new DateTimeImmutable('2026-03-06 07:59:59', $utc)))->toBe([]);
})->with('article repositories');
