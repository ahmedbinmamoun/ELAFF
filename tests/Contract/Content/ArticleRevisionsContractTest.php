<?php

declare(strict_types=1);

use Elaf\Content\Application\Article\Revision\ArticleRevisions;
use Elaf\Content\Domain\Article\ArticleRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentArticleRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentArticleRevisions;
use Elaf\Shared\Application\Validation\RecordNotFound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Builders\Articles;
use Tests\Support\Contract\ContentFixtures;
use Tests\Support\Contract\EloquentContentFixtures;
use Tests\Support\Contract\InMemoryContentFixtures;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\InMemoryArticleRepository;
use Tests\Support\Fakes\InMemoryArticleRevisions;

uses(RefreshDatabase::class);

dataset('revision stores', [
    'in-memory' => fn (): array => [new InMemoryArticleRevisions, new InMemoryArticleRepository, new InMemoryContentFixtures],
    'eloquent' => fn (): array => [new EloquentArticleRevisions, app(EloquentArticleRepository::class), new EloquentContentFixtures],
]);

it('records snapshots and lists them newest first', function (ArticleRevisions $revisions, ArticleRepository $articles, ContentFixtures $fx): void {
    $clock = new FrozenClock('2026-03-05 10:00:00');
    $user = $fx->user();
    $category = $fx->category()->value;
    $author = $fx->author()->value;
    $article = Elaf\Content\Domain\Article\Article::draft(
        $articles->nextId(),
        Elaf\Content\Domain\Article\ArticleOrigin::Staff,
        $user,
        Articles::content('الأولى', categoryId: $category, authorIds: [$author]),
        $clock,
    );
    $articles->save($article);

    $revisions->record($article->id(), $user, $article->content(), $clock->now());
    $clock->advance('PT5M');
    $second = Articles::content('الثانية', categoryId: $category, authorIds: [$author]);
    $revisions->record($article->id(), $user, $second, $clock->now());

    $list = $revisions->forArticle($article->id());

    expect($list)->toHaveCount(2)
        ->and($list[0]->content->equals($second))->toBeTrue()
        ->and($list[0]->createdAt->getTimestamp())->toBe($clock->now()->getTimestamp())
        ->and($list[0]->userId)->toEqual($user)
        ->and($list[1]->content->title->value)->toBe('الأولى')
        ->and($revisions->get($list[1]->id)->content->title->value)->toBe('الأولى')
        ->and($revisions->forArticle(Elaf\Content\Domain\Article\ArticleId::fromInt(9999)))->toBe([]);
})->with('revision stores');

it('throws RecordNotFound for an unknown revision', function (ArticleRevisions $revisions, ArticleRepository $articles, ContentFixtures $fx): void {
    $revisions->get(9999);
})->with('revision stores')->throws(RecordNotFound::class);
