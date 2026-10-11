<?php

declare(strict_types=1);

use Elaf\Content\Domain\Article\ArticleBody;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleSource;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Article\Events\ArticleApproved;
use Elaf\Content\Domain\Article\Events\ArticleAssignedToIssue;
use Elaf\Content\Domain\Article\Events\ArticleBreakingCleared;
use Elaf\Content\Domain\Article\Events\ArticleCreated;
use Elaf\Content\Domain\Article\Events\ArticleMarkedBreaking;
use Elaf\Content\Domain\Article\Events\ArticlePromoted;
use Elaf\Content\Domain\Article\Events\ArticlePublished;
use Elaf\Content\Domain\Article\Events\ArticleReturnedToWriter;
use Elaf\Content\Domain\Article\Events\ArticleScheduled;
use Elaf\Content\Domain\Article\Events\ArticleSubmittedForReview;
use Elaf\Content\Domain\Article\Events\ArticleUnpublished;
use Elaf\Content\Domain\Article\Events\ArticleUpdated;
use Elaf\Content\Domain\Article\Exception\ArticleNotPublishable;
use Elaf\Content\Domain\Article\Exception\ArticleWorkflowViolation;
use Elaf\Content\Domain\Article\Exception\InvalidArticleContent;
use Elaf\Content\Domain\Article\FeaturedImage;
use Elaf\Content\Domain\Article\IssuePlacement;
use Elaf\Content\Domain\Article\Title;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Shared\Domain\Text\Slug;
use Tests\Support\Builders\Articles;
use Tests\Support\Fakes\FrozenClock;

beforeEach(function (): void {
    $this->clock = new FrozenClock('2026-03-05 10:00:00');
    $this->writer = UserId::fromInt(Articles::WRITER);
    $this->editor = UserId::fromInt(Articles::EDITOR);
    $this->chief = UserId::fromInt(Articles::CHIEF);
});

it('starts as a draft owned by its writer and records the creation', function (): void {
    $article = Articles::draft($this->clock, origin: ArticleOrigin::Contributor);

    expect($article->status())->toBe(ArticleStatus::Draft)
        ->and($article->isCreatedBy($this->writer))->toBeTrue()
        ->and($article->createdAt())->toEqual($this->clock->now())
        ->and($article->origin())->toBe(ArticleOrigin::Contributor);

    $events = $article->releaseEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(ArticleCreated::class)
        ->and($events[0]->actorId)->toEqual($this->writer);
});

it('records who submitted and when, and clears an earlier return note', function (): void {
    $article = Articles::inState(ArticleStatus::Returned, $this->clock);
    expect($article->returnNote())->toBe('يرجى المراجعة');

    $this->clock->advance('PT1H');
    $article->submitForReview($this->writer, $this->clock);

    expect($article->submittedAt())->toEqual($this->clock->now())
        ->and($article->returnNote())->toBeNull()
        ->and($article->releaseEvents()[0])->toBeInstanceOf(ArticleSubmittedForReview::class);
});

it('never lets anyone approve their own article', function (): void {
    $article = Articles::inState(ArticleStatus::InReview, $this->clock);

    expect(fn () => $article->approve($this->writer, $this->clock))->toThrow(ArticleWorkflowViolation::class);
    expect($article->status())->toBe(ArticleStatus::InReview);
});

it('records the reviewer on approval', function (): void {
    $article = Articles::inState(ArticleStatus::InReview, $this->clock);
    $article->approve($this->editor, $this->clock);

    expect($article->reviewedBy())->toEqual($this->editor)
        ->and($article->releaseEvents()[0])->toBeInstanceOf(ArticleApproved::class);
});

it('requires a note to return an article', function (string $note): void {
    $article = Articles::inState(ArticleStatus::InReview, $this->clock);

    expect(fn () => $article->returnToWriter($this->editor, $note, $this->clock))->toThrow(ArticleWorkflowViolation::class);
})->with(['', '   ']);

it('keeps the return note for the writer', function (): void {
    $article = Articles::inState(ArticleStatus::InReview, $this->clock);
    $article->returnToWriter($this->editor, '  أضف مصدراً  ', $this->clock);

    $event = $article->releaseEvents()[0];
    expect($article->returnNote())->toBe('أضف مصدراً')
        ->and($event)->toBeInstanceOf(ArticleReturnedToWriter::class)
        ->and($event->details())->toBe(['note' => 'أضف مصدراً']);
});

it('publishes now and records the publication time', function (): void {
    $article = Articles::inState(ArticleStatus::Approved, $this->clock);
    $this->clock->advance('PT30M');
    $article->publish($this->chief, $this->clock);

    expect($article->publishedAt())->toEqual($this->clock->now())
        ->and($article->status()->isLive())->toBeTrue();

    $event = $article->releaseEvents()[0];
    expect($event)->toBeInstanceOf(ArticlePublished::class)
        ->and($event->actorId)->toEqual($this->chief);
});

it('only schedules in the future', function (string $offset): void {
    $article = Articles::inState(ArticleStatus::Approved, $this->clock);

    expect(fn () => $article->schedule($this->clock->now()->modify($offset), $this->chief, $this->clock))
        ->toThrow(ArticleWorkflowViolation::class);
})->with(['now' => ['+0 seconds'], 'past' => ['-1 minute']]);

it('publishes a due scheduled article at its scheduled time', function (): void {
    $article = Articles::inState(ArticleStatus::Approved, $this->clock);
    $at = $this->clock->now()->modify('+1 hour');
    $article->schedule($at, $this->chief, $this->clock);

    expect($article->scheduledFor())->toEqual($at)
        ->and($article->isDueForPublication($this->clock->now()))->toBeFalse()
        ->and($article->releaseEvents()[0])->toBeInstanceOf(ArticleScheduled::class);

    $this->clock->advance('PT1H1M');
    expect($article->isDueForPublication($this->clock->now()))->toBeTrue();

    $article->publish($this->chief, $this->clock);
    expect($article->publishedAt())->toEqual($at)
        ->and($article->scheduledFor())->toBeNull();
});

it('publishes a scheduled article immediately when asked before its time', function (): void {
    $article = Articles::inState(ArticleStatus::Scheduled, $this->clock);
    $article->publish($this->chief, $this->clock);

    expect($article->publishedAt())->toEqual($this->clock->now())
        ->and($article->scheduledFor())->toBeNull();
});

it('keeps the original publication date when republished', function (): void {
    $article = Articles::inState(ArticleStatus::Published, $this->clock);
    $firstPublished = $article->publishedAt();

    $this->clock->advance('P2D');
    $article->unpublish($this->chief, $this->clock);
    expect($article->releaseEvents()[0])->toBeInstanceOf(ArticleUnpublished::class);

    $this->clock->advance('P1D');
    $article->publish($this->chief, $this->clock);
    expect($article->publishedAt())->toEqual($firstPublished);
});

it('refuses to publish without body (or image) and author', function (): void {
    $content = Articles::content(body: '<p> </p>', authorIds: []);
    $article = Articles::inState(ArticleStatus::Approved, $this->clock, $content);

    try {
        $article->publish($this->chief, $this->clock);
        $this->fail('Expected ArticleNotPublishable.');
    } catch (ArticleNotPublishable $e) {
        expect($e->missing)->toBe(['body or featured image', 'author']);
    }

    expect($article->status())->toBe(ArticleStatus::Approved);
    expect(fn () => $article->schedule($this->clock->now()->modify('+1 day'), $this->chief, $this->clock))
        ->toThrow(ArticleNotPublishable::class);
});

it('publishes an official notice that has only a featured image (FR-ADS-04)', function (): void {
    $content = new ArticleContent(
        title: new Title('إعلان رسمي'),
        slug: Slug::fromText('إعلان رسمي'),
        body: ArticleBody::empty(),
        source: new ArticleSource('إيلاف'),
        categoryId: CategoryId::fromInt(1),
        authorIds: [Elaf\Content\Domain\Author\AuthorId::fromInt(1)],
        featuredImage: new FeaturedImage('notices/central-bank.webp'),
    );
    $article = Articles::inState(ArticleStatus::Approved, $this->clock, $content);
    $article->publish($this->chief, $this->clock);

    expect($article->status())->toBe(ArticleStatus::Published);
});

it('cannot be published before it was created', function (): void {
    $article = Articles::inState(ArticleStatus::Approved, $this->clock);
    $this->clock->travelTo($this->clock->now()->modify('-1 day'));

    expect(fn () => $article->publish($this->chief, $this->clock))->toThrow(ArticleWorkflowViolation::class);
});

it('records a field-level diff when revised and nothing when unchanged', function (): void {
    $article = Articles::inState(ArticleStatus::Published, $this->clock);
    $article->revise(Articles::content(), $this->editor, $this->clock);
    expect($article->releaseEvents())->toBe([]);

    $article->revise(Articles::content(title: 'عنوان جديد'), $this->editor, $this->clock);
    $event = $article->releaseEvents()[0];

    expect($event)->toBeInstanceOf(ArticleUpdated::class)
        ->and($event->changes)->toBe([
            'title' => ['عنوان المقال التجريبي', 'عنوان جديد'],
            'slug' => ['عنوان-المقال-التجريبي', 'عنوان-جديد'],
        ])
        ->and($article->updatedBy())->toEqual($this->editor)
        ->and($article->status())->toBe(ArticleStatus::Published);
});

it('marks breaking news with an optional future expiry (FR-ART-06)', function (): void {
    $article = Articles::inState(ArticleStatus::Published, $this->clock);
    $until = $this->clock->now()->modify('+3 hours');

    $article->markBreaking($until, $this->chief, $this->clock);
    expect($article->isBreakingAt($this->clock->now()))->toBeTrue()
        ->and($article->isBreakingAt($until))->toBeFalse()
        ->and($article->releaseEvents()[0])->toBeInstanceOf(ArticleMarkedBreaking::class);

    $article->markBreaking(null, $this->chief, $this->clock);
    expect($article->isBreakingAt($this->clock->now()->modify('+1 year')))->toBeTrue();

    $article->releaseEvents();
    $article->unmarkBreaking($this->chief, $this->clock);
    expect($article->isBreaking())->toBeFalse()
        ->and($article->breakingUntil())->toBeNull()
        ->and($article->releaseEvents()[0])->toBeInstanceOf(ArticleBreakingCleared::class);

    $article->unmarkBreaking($this->chief, $this->clock);
    expect($article->releaseEvents())->toBe([]);
});

it('rejects a breaking expiry in the past', function (): void {
    $article = Articles::inState(ArticleStatus::Published, $this->clock);

    expect(fn () => $article->markBreaking($this->clock->now(), $this->chief, $this->clock))
        ->toThrow(ArticleWorkflowViolation::class);
});

it('promotes to the hero with a non-negative priority (FR-ART-07)', function (): void {
    $article = Articles::inState(ArticleStatus::Published, $this->clock);
    $article->promote(true, 5, $this->chief, $this->clock);

    expect($article->isFeatured())->toBeTrue()
        ->and($article->homepagePriority())->toBe(5)
        ->and($article->releaseEvents()[0])->toBeInstanceOf(ArticlePromoted::class);

    expect(fn () => $article->promote(true, -1, $this->chief, $this->clock))->toThrow(InvalidArticleContent::class);
});

it('links to a print issue page and ignores a repeat of the same link', function (): void {
    $article = Articles::inState(ArticleStatus::Published, $this->clock);
    $placement = new IssuePlacement(IssueId::fromInt(7), 3);

    $article->assignToIssue($placement, $this->editor, $this->clock);
    $article->assignToIssue(new IssuePlacement(IssueId::fromInt(7), 3), $this->editor, $this->clock);

    expect($article->issue())->toBe($placement)
        ->and($article->releaseEvents())->toHaveCount(1);

    $article->assignToIssue(null, $this->editor, $this->clock);
    expect($article->issue())->toBeNull()
        ->and($article->releaseEvents()[0])->toBeInstanceOf(ArticleAssignedToIssue::class);
});

it('rebuilds from storage without recording events', function (): void {
    $article = Elaf\Content\Domain\Article\Article::reconstitute(
        id: ArticleId::fromInt(9),
        origin: ArticleOrigin::Staff,
        createdBy: $this->writer,
        createdAt: $this->clock->now(),
        updatedBy: $this->editor,
        content: Articles::content(),
        status: ArticleStatus::Approved,
        publishedAt: null,
        scheduledFor: null,
        submittedAt: $this->clock->now(),
        reviewedBy: $this->editor,
        returnNote: null,
        breaking: false,
        breakingUntil: null,
        featured: false,
        homepagePriority: 0,
        issue: null,
    );

    expect($article->releaseEvents())->toBe([])
        ->and($article->status())->toBe(ArticleStatus::Approved);
});
