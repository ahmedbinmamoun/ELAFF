<?php

declare(strict_types=1);

use Elaf\Content\Application\Article\PublishDueScheduledArticles\PublishDueScheduledArticlesHandler;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Article\Events\ArticlePublished;
use Elaf\Identity\Domain\UserId;
use Tests\Support\Builders\Articles;
use Tests\Support\ContentKit;

beforeEach(function (): void {
    $this->kit = new ContentKit;
    $this->schedule = function (int $id, string $at, ?Elaf\Content\Domain\Article\ArticleContent $content = null): void {
        $article = Articles::inState(ArticleStatus::Approved, $this->kit->clock, $content, $id);
        $article->schedule(new DateTimeImmutable($at, new DateTimeZone('Africa/Khartoum')), UserId::fromInt(Articles::CHIEF), $this->kit->clock);
        $article->releaseEvents();
        $this->kit->articles->save($article);
    };
});

it('publishes due articles at their scheduled time and leaves the others (FR-ART-05)', function (): void {
    ($this->schedule)(1, '2026-03-05 12:00:00');
    ($this->schedule)(2, '2026-03-05 11:00:00');
    ($this->schedule)(3, '2026-03-06 09:00:00');
    $this->kit->clock->travelTo(new DateTimeImmutable('2026-03-05 12:00:30', new DateTimeZone('Africa/Khartoum')));

    $result = $this->kit->make(PublishDueScheduledArticlesHandler::class)->handle();

    expect(array_map(static fn (ArticleId $id): int => $id->value, $result->published))->toBe([2, 1])
        ->and($result->failed)->toBe([])
        ->and($this->kit->articles->get(ArticleId::fromInt(1))->publishedAt()?->format('H:i'))->toBe('12:00')
        ->and($this->kit->articles->get(ArticleId::fromInt(3))->status())->toBe(ArticleStatus::Scheduled);

    $events = $this->kit->events->ofType(ArticlePublished::class);
    expect($events)->toHaveCount(2)
        ->and($events[0]->actorId->value)->toBe(Articles::CHIEF);
});

it('reports an article that can no longer be published without blocking the rest', function (): void {
    ($this->schedule)(1, '2026-03-05 11:00:00');
    ($this->schedule)(2, '2026-03-05 11:00:00');

    $broken = $this->kit->articles->get(ArticleId::fromInt(1));
    $broken->revise(Articles::content(authorIds: []), UserId::fromInt(Articles::EDITOR), $this->kit->clock);
    $broken->releaseEvents();
    $this->kit->articles->save($broken);

    $this->kit->clock->advance('PT2H');
    $result = $this->kit->make(PublishDueScheduledArticlesHandler::class)->handle();

    expect(array_keys($result->failed))->toBe([1])
        ->and($result->published)->toHaveCount(1)
        ->and($this->kit->articles->get(ArticleId::fromInt(1))->status())->toBe(ArticleStatus::Scheduled);
});
