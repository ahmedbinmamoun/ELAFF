<?php

declare(strict_types=1);

use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftCommand;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftHandler;
use Elaf\Content\Application\Article\RestoreArticleRevision\RestoreArticleRevisionCommand;
use Elaf\Content\Application\Article\RestoreArticleRevision\RestoreArticleRevisionHandler;
use Elaf\Content\Application\Article\UpdateArticle\UpdateArticleCommand;
use Elaf\Content\Application\Article\UpdateArticle\UpdateArticleHandler;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\RecordNotFound;
use Tests\Support\ContentKit;

beforeEach(function (): void {
    $this->kit = (new ContentKit)->grant(ContentPermission::CreateArticles);
    $category = $this->kit->category();
    $author = $this->kit->author();
    $input = fn (string $title) => $this->kit->input(['title' => $title, 'categoryId' => $category, 'authorIds' => [$author]]);

    $this->id = $this->kit->make(CreateArticleDraftHandler::class)->handle(new CreateArticleDraftCommand(1, ArticleOrigin::Staff, $input('النسخة الأولى')));
    $this->kit->make(UpdateArticleHandler::class)->handle(new UpdateArticleCommand(1, $this->id->value, $input('النسخة الثانية')));
});

it('restores an earlier revision as a new revision (FR-ART-08)', function (): void {
    $this->kit->grant(ContentPermission::EditAnyArticle);
    [$second, $first] = $this->kit->revisions->forArticle($this->id);

    $this->kit->make(RestoreArticleRevisionHandler::class)->handle(new RestoreArticleRevisionCommand(2, $first->id));

    $revisions = $this->kit->revisions->forArticle($this->id);
    expect($this->kit->articles->get($this->id)->content()->title->value)->toBe('النسخة الأولى')
        ->and($revisions)->toHaveCount(3)
        ->and($revisions[0]->userId->value)->toBe(2)
        ->and($revisions[0]->content->equals($first->content))->toBeTrue();
});

it('is reserved to editors', function (): void {
    $first = $this->kit->revisions->forArticle($this->id)[1];

    expect(fn () => $this->kit->make(RestoreArticleRevisionHandler::class)->handle(new RestoreArticleRevisionCommand(1, $first->id)))
        ->toThrow(NotAuthorized::class);
});

it('fails on an unknown revision', function (): void {
    $this->kit->grant(ContentPermission::EditAnyArticle);

    expect(fn () => $this->kit->make(RestoreArticleRevisionHandler::class)->handle(new RestoreArticleRevisionCommand(2, 999)))
        ->toThrow(RecordNotFound::class);
});

it('leaves out tags, authors and series deleted since the snapshot', function (): void {
    $this->kit->grant(ContentPermission::EditAnyArticle);
    $category = $this->kit->category('قسم ثابت');
    $kept = $this->kit->author('باق');
    $gone = $this->kit->author('محذوف');
    $series = $this->kit->aSeries();
    $id = $this->kit->make(CreateArticleDraftHandler::class)->handle(new CreateArticleDraftCommand(1, ArticleOrigin::Staff, $this->kit->input([
        'title' => 'قديم', 'categoryId' => $category, 'authorIds' => [$kept, $gone], 'tagNames' => ['وسم'], 'seriesId' => $series, 'seriesPart' => 1,
    ])));
    $snapshot = $this->kit->revisions->forArticle($id)[0];
    $this->kit->make(UpdateArticleHandler::class)->handle(new UpdateArticleCommand(1, $id->value, $this->kit->input([
        'title' => 'جديد', 'categoryId' => $category, 'authorIds' => [$kept],
    ])));

    $this->kit->authors->delete(Elaf\Content\Domain\Author\AuthorId::fromInt($gone));
    $this->kit->series->delete(Elaf\Content\Domain\Series\SeriesId::fromInt($series));
    foreach ($snapshot->content->tagIds as $tag) {
        $this->kit->tags->delete($tag);
    }

    $this->kit->make(RestoreArticleRevisionHandler::class)->handle(new RestoreArticleRevisionCommand(2, $snapshot->id));
    $content = $this->kit->articles->get($id)->content();

    expect($content->title->value)->toBe('قديم')
        ->and(array_map(static fn ($a): int => $a->value, $content->authorIds))->toBe([$kept])
        ->and($content->tagIds)->toBe([])
        ->and($content->series)->toBeNull();
});

it('refuses to restore a snapshot whose category was deleted', function (): void {
    $this->kit->grant(ContentPermission::EditAnyArticle);
    $first = $this->kit->revisions->forArticle($this->id)[1];
    $this->kit->categories->delete($first->content->categoryId);

    expect(fn () => $this->kit->make(RestoreArticleRevisionHandler::class)->handle(new RestoreArticleRevisionCommand(2, $first->id)))
        ->toThrow(Elaf\Shared\Application\Validation\InvalidInput::class);
});
