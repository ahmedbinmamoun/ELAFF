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
