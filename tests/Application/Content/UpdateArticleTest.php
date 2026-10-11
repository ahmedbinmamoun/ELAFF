<?php

declare(strict_types=1);

use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftCommand;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftHandler;
use Elaf\Content\Application\Article\SubmitArticleForReview\SubmitArticleForReviewCommand;
use Elaf\Content\Application\Article\SubmitArticleForReview\SubmitArticleForReviewHandler;
use Elaf\Content\Application\Article\UpdateArticle\UpdateArticleCommand;
use Elaf\Content\Application\Article\UpdateArticle\UpdateArticleHandler;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\Events\ArticleUpdated;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Tests\Support\ContentKit;

beforeEach(function (): void {
    $this->owner = 1;
    $this->other = 2;
    $this->kit = (new ContentKit)->grant(ContentPermission::CreateArticles);
    $this->category = $this->kit->category();
    $this->author = $this->kit->author();
    $this->input = fn (string $title) => $this->kit->input(['title' => $title, 'categoryId' => $this->category, 'authorIds' => [$this->author]]);
    $this->id = $this->kit->make(CreateArticleDraftHandler::class)
        ->handle(new CreateArticleDraftCommand($this->owner, ArticleOrigin::Staff, ($this->input)('الأصل')))->value;
    $this->update = fn (int $actor, string $title) => $this->kit->make(UpdateArticleHandler::class)
        ->handle(new UpdateArticleCommand($actor, $this->id, ($this->input)($title)));
    $this->submit = fn () => $this->kit->make(SubmitArticleForReviewHandler::class)
        ->handle(new SubmitArticleForReviewCommand($this->owner, $this->id));
    $this->title = fn (): string => $this->kit->articles->get(ArticleId::fromInt($this->id))->content()->title->value;
});

it('lets the writer edit their own draft and records a revision with the diff', function (): void {
    ($this->update)($this->owner, 'معدل');

    $events = $this->kit->events->ofType(ArticleUpdated::class);
    expect(($this->title)())->toBe('معدل')
        ->and($this->kit->revisions->forArticle(ArticleId::fromInt($this->id)))->toHaveCount(2)
        ->and($events)->toHaveCount(1)
        ->and($events[0]->changes['title'])->toBe(['الأصل', 'معدل']);
});

it('saves nothing when nothing changed', function (): void {
    $saves = $this->kit->articles->saves;
    ($this->update)($this->owner, 'الأصل');

    expect($this->kit->articles->saves)->toBe($saves)
        ->and($this->kit->revisions->forArticle(ArticleId::fromInt($this->id)))->toHaveCount(1)
        ->and($this->kit->events->ofType(ArticleUpdated::class))->toBe([]);
});

it('locks the article for its writer once submitted (FR-CON-07)', function (): void {
    ($this->submit)();

    expect(fn () => ($this->update)($this->owner, 'محاولة'))->toThrow(NotAuthorized::class, 'locked');
    expect(($this->title)())->toBe('الأصل');
});

it('does not let a writer edit someone else\'s article', function (): void {
    expect(fn () => ($this->update)($this->other, 'محاولة'))->toThrow(NotAuthorized::class);
});

it('lets editors edit any article in any state', function (): void {
    ($this->submit)();
    $this->kit->grant(ContentPermission::EditAnyArticle);

    ($this->update)($this->other, 'تحرير المحرر');

    expect(($this->title)())->toBe('تحرير المحرر');
});
