<?php

declare(strict_types=1);

use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Article\Exception\IllegalArticleTransition;
use Elaf\Identity\Domain\UserId;
use Tests\Support\Builders\Articles;
use Tests\Support\Fakes\FrozenClock;

/*
 * FR-ART-04: every workflow action from every state. Legal moves land in the
 * expected state; everything else throws IllegalArticleTransition.
 */

/** @return array<string, array{callable(Article, FrozenClock): void, list<ArticleStatus>, ArticleStatus}> */
function workflow_actions(): array
{
    $writer = UserId::fromInt(Articles::WRITER);
    $editor = UserId::fromInt(Articles::EDITOR);
    $chief = UserId::fromInt(Articles::CHIEF);

    return [
        'submit' => [fn (Article $a, FrozenClock $c) => $a->submitForReview($writer, $c), [ArticleStatus::Draft, ArticleStatus::Returned], ArticleStatus::InReview],
        'withdraw' => [fn (Article $a, FrozenClock $c) => $a->withdraw($writer, $c), [ArticleStatus::InReview], ArticleStatus::Draft],
        'approve' => [fn (Article $a, FrozenClock $c) => $a->approve($editor, $c), [ArticleStatus::InReview], ArticleStatus::Approved],
        'return' => [fn (Article $a, FrozenClock $c) => $a->returnToWriter($editor, 'ملاحظة', $c), [ArticleStatus::InReview], ArticleStatus::Returned],
        'schedule' => [fn (Article $a, FrozenClock $c) => $a->schedule($c->now()->modify('+2 days'), $chief, $c), [ArticleStatus::Approved, ArticleStatus::Scheduled], ArticleStatus::Scheduled],
        'publish' => [fn (Article $a, FrozenClock $c) => $a->publish($chief, $c), [ArticleStatus::Approved, ArticleStatus::Scheduled, ArticleStatus::Unpublished], ArticleStatus::Published],
        'unpublish' => [fn (Article $a, FrozenClock $c) => $a->unpublish($chief, $c), [ArticleStatus::Published], ArticleStatus::Unpublished],
    ];
}

$cases = [];
foreach (array_keys(workflow_actions()) as $action) {
    foreach (ArticleStatus::cases() as $from) {
        $cases["{$action} from {$from->value}"] = [$action, $from];
    }
}

it('applies each action only from its allowed states', function (string $action, ArticleStatus $from): void {
    [$apply, $allowedFrom, $to] = workflow_actions()[$action];
    $clock = new FrozenClock;
    $article = Articles::inState($from, $clock);

    if (in_array($from, $allowedFrom, true)) {
        $apply($article, $clock);
        expect($article->status())->toBe($to);

        return;
    }

    expect(fn () => $apply($article, $clock))->toThrow(IllegalArticleTransition::class);
    expect($article->status())->toBe($from);
})->with($cases);

it('keeps the status enum transition table consistent with the actions', function (): void {
    $reachable = [];
    foreach (workflow_actions() as [, $allowedFrom, $to]) {
        foreach ($allowedFrom as $from) {
            $reachable[$from->value][] = $to;
        }
    }

    foreach (ArticleStatus::cases() as $status) {
        $expected = array_unique(array_map(static fn (ArticleStatus $s): string => $s->value, $reachable[$status->value] ?? []));
        $actual = array_map(static fn (ArticleStatus $s): string => $s->value, $status->allowedTransitions());
        sort($expected);
        sort($actual);

        expect($actual)->toBe(array_values($expected), "transitions from {$status->value}");
    }
});

it('lets the writer edit only draft and returned articles', function (ArticleStatus $status, bool $editable): void {
    expect($status->isEditableByAuthor())->toBe($editable);
})->with([
    [ArticleStatus::Draft, true],
    [ArticleStatus::Returned, true],
    [ArticleStatus::InReview, false],
    [ArticleStatus::Approved, false],
    [ArticleStatus::Scheduled, false],
    [ArticleStatus::Published, false],
    [ArticleStatus::Unpublished, false],
]);
