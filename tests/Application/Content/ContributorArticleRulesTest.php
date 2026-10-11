<?php

declare(strict_types=1);

use Elaf\Content\Application\Article\ArticleContentFactory;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftCommand;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftHandler;
use Elaf\Content\Application\Article\PublishArticle\PublishArticleCommand;
use Elaf\Content\Application\Article\PublishArticle\PublishArticleHandler;
use Elaf\Content\Application\Article\SubmitArticleForReview\SubmitArticleForReviewCommand;
use Elaf\Content\Application\Article\SubmitArticleForReview\SubmitArticleForReviewHandler;
use Elaf\Content\Application\Article\UpdateArticle\UpdateArticleCommand;
use Elaf\Content\Application\Article\UpdateArticle\UpdateArticleHandler;
use Elaf\Content\Application\Author\AuthorData;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Domain\Text\Slug;
use Tests\Support\Builders\Articles;
use Tests\Support\ContentKit;

const CONTRIBUTOR = 50;

beforeEach(function (): void {
    $this->kit = (new ContentKit)->grant(ContentPermission::CreateArticles);
    $this->category = $this->kit->category('رأي', openToContributors: true);
    $this->own = $this->kit->authors->create(new AuthorData('الضيف', Slug::fromText('الضيف'), userId: UserId::fromInt(CONTRIBUTOR)));
    $this->other = AuthorId::fromInt($this->kit->author('كاتب آخر'));
    $this->create = fn (array $overrides = []): ArticleId => $this->kit->make(CreateArticleDraftHandler::class)->handle(new CreateArticleDraftCommand(
        CONTRIBUTOR,
        ArticleOrigin::Contributor,
        $this->kit->input(['categoryId' => $this->category, 'authorIds' => [$this->other->value], ...$overrides]),
    ));
});

it('always signs a contributor article with the contributor\'s own author (FR-CON-04)', function (): void {
    $id = ($this->create)();

    expect($this->kit->articles->get($id)->content()->authorIds)->toEqual([$this->own]);

    $this->kit->make(UpdateArticleHandler::class)->handle(new UpdateArticleCommand(CONTRIBUTOR, $id->value, $this->kit->input([
        'title' => 'معدل', 'categoryId' => $this->category, 'authorIds' => [$this->other->value],
    ])));
    expect($this->kit->articles->get($id)->content()->authorIds)->toEqual([$this->own]);
});

it('lets an editor set the byline of a contributor article', function (): void {
    $id = ($this->create)();
    $this->kit->grant(ContentPermission::EditAnyArticle);

    $this->kit->make(UpdateArticleHandler::class)->handle(new UpdateArticleCommand(2, $id->value, $this->kit->input([
        'categoryId' => $this->category, 'authorIds' => [$this->own->value, $this->other->value],
    ])));

    expect($this->kit->articles->get($id)->content()->authorIds)->toEqual([$this->own, $this->other]);
});

it('refuses a contributor without an author profile', function (): void {
    expect(fn () => $this->kit->make(CreateArticleDraftHandler::class)->handle(new CreateArticleDraftCommand(
        99, ArticleOrigin::Contributor, $this->kit->input(['categoryId' => $this->category]),
    )))->toThrow(NotAuthorized::class);
});

it('limits contributor bodies to a shorter length (FR-CON-10)', function (): void {
    $max = ArticleContentFactory::MAX_CONTRIBUTOR_BODY_LENGTH;

    expect(fn () => ($this->create)(['body' => str_repeat('a', $max + 1)]))->toThrow(InvalidInput::class);
    ($this->create)(['body' => str_repeat('a', $max)]);
});

it('allows at most five contributor articles in review at once (FR-CON-10)', function (): void {
    $submit = fn (ArticleId $id) => $this->kit->make(SubmitArticleForReviewHandler::class)->handle(new SubmitArticleForReviewCommand(CONTRIBUTOR, $id->value));

    for ($i = 0; $i < SubmitArticleForReviewHandler::MAX_IN_REVIEW; $i++) {
        $submit(($this->create)(['title' => "مقال {$i}"]));
    }

    $sixth = ($this->create)(['title' => 'السادس']);
    try {
        $submit($sixth);
        $this->fail('Expected the limit.');
    } catch (InvalidInput $e) {
        expect($e->reason)->toBe('too_many_in_review');
    }

    expect($this->kit->articles->get($sixth)->status())->toBe(ArticleStatus::Draft);
});

it('lets editors publish contributor articles only when the setting is on (FR-CON-08)', function (): void {
    $this->kit->grant(ContentPermission::ReviewArticles);
    $contributorArticle = Articles::inState(ArticleStatus::Approved, $this->kit->clock, id: 7);
    $this->kit->articles->save($contributorArticle);
    $publish = fn (int $id) => $this->kit->make(PublishArticleHandler::class)->handle(new PublishArticleCommand(Articles::EDITOR, $id));

    $draft = Elaf\Content\Domain\Article\Article::draft(ArticleId::fromInt(8), ArticleOrigin::Contributor, UserId::fromInt(1), Articles::content(), $this->kit->clock);
    $draft->submitForReview(UserId::fromInt(1), $this->kit->clock);
    $draft->approve(UserId::fromInt(3), $this->kit->clock);
    $this->kit->articles->save($draft);

    expect(fn () => $publish(8))->toThrow(NotAuthorized::class);

    $this->kit->settings->editorsCanPublish = true;
    $publish(8);
    expect($this->kit->articles->get(ArticleId::fromInt(8))->status())->toBe(ArticleStatus::Published);

    expect(fn () => $publish(7))->toThrow(NotAuthorized::class);
});
