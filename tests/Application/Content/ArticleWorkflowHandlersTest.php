<?php

declare(strict_types=1);

use Elaf\Content\Application\Article\ApproveArticle\ApproveArticleCommand;
use Elaf\Content\Application\Article\ApproveArticle\ApproveArticleHandler;
use Elaf\Content\Application\Article\MarkArticleBreaking\MarkArticleBreakingCommand;
use Elaf\Content\Application\Article\MarkArticleBreaking\MarkArticleBreakingHandler;
use Elaf\Content\Application\Article\PromoteArticle\PromoteArticleCommand;
use Elaf\Content\Application\Article\PromoteArticle\PromoteArticleHandler;
use Elaf\Content\Application\Article\PublishArticle\PublishArticleCommand;
use Elaf\Content\Application\Article\PublishArticle\PublishArticleHandler;
use Elaf\Content\Application\Article\ReturnArticleToWriter\ReturnArticleToWriterCommand;
use Elaf\Content\Application\Article\ReturnArticleToWriter\ReturnArticleToWriterHandler;
use Elaf\Content\Application\Article\ScheduleArticle\ScheduleArticleCommand;
use Elaf\Content\Application\Article\ScheduleArticle\ScheduleArticleHandler;
use Elaf\Content\Application\Article\SubmitArticleForReview\SubmitArticleForReviewCommand;
use Elaf\Content\Application\Article\SubmitArticleForReview\SubmitArticleForReviewHandler;
use Elaf\Content\Application\Article\UnmarkArticleBreaking\UnmarkArticleBreakingCommand;
use Elaf\Content\Application\Article\UnmarkArticleBreaking\UnmarkArticleBreakingHandler;
use Elaf\Content\Application\Article\UnpublishArticle\UnpublishArticleCommand;
use Elaf\Content\Application\Article\UnpublishArticle\UnpublishArticleHandler;
use Elaf\Content\Application\Article\WithdrawSubmission\WithdrawSubmissionCommand;
use Elaf\Content\Application\Article\WithdrawSubmission\WithdrawSubmissionHandler;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Article\Events\ArticleApproved;
use Elaf\Content\Domain\Article\Events\ArticlePublished;
use Elaf\Content\Domain\Article\Exception\ArticleWorkflowViolation;
use Elaf\Content\Domain\Article\Exception\IllegalArticleTransition;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Tests\Support\Builders\Articles;
use Tests\Support\ContentKit;

beforeEach(function (): void {
    $this->kit = new ContentKit;
    $this->given = function (ArticleStatus $status): void {
        $this->kit->articles->save(Articles::inState($status, $this->kit->clock));
    };
    $this->status = fn (): ArticleStatus => $this->kit->articles->get(ArticleId::fromInt(1))->status();
});

/**
 * @return array<string, array{class-string, object, ArticleStatus, ?ContentPermission, ArticleStatus}>
 */
function workflow_handlers(): array
{
    $at = new DateTimeImmutable('2026-03-07 08:00:00', new DateTimeZone('Africa/Khartoum'));

    return [
        'submit' => [SubmitArticleForReviewHandler::class, new SubmitArticleForReviewCommand(Articles::WRITER, 1), ArticleStatus::Draft, null, ArticleStatus::InReview],
        'withdraw' => [WithdrawSubmissionHandler::class, new WithdrawSubmissionCommand(Articles::WRITER, 1), ArticleStatus::InReview, null, ArticleStatus::Draft],
        'approve' => [ApproveArticleHandler::class, new ApproveArticleCommand(Articles::EDITOR, 1), ArticleStatus::InReview, ContentPermission::ReviewArticles, ArticleStatus::Approved],
        'return' => [ReturnArticleToWriterHandler::class, new ReturnArticleToWriterCommand(Articles::EDITOR, 1, 'راجع الأرقام'), ArticleStatus::InReview, ContentPermission::ReviewArticles, ArticleStatus::Returned],
        'publish' => [PublishArticleHandler::class, new PublishArticleCommand(Articles::CHIEF, 1), ArticleStatus::Approved, ContentPermission::PublishArticles, ArticleStatus::Published],
        'schedule' => [ScheduleArticleHandler::class, new ScheduleArticleCommand(Articles::CHIEF, 1, $at), ArticleStatus::Approved, ContentPermission::PublishArticles, ArticleStatus::Scheduled],
        'unpublish' => [UnpublishArticleHandler::class, new UnpublishArticleCommand(Articles::CHIEF, 1), ArticleStatus::Published, ContentPermission::PublishArticles, ArticleStatus::Unpublished],
    ];
}

it('moves the article, saves it and dispatches its events', function (string $handler, object $command, ArticleStatus $from, ?ContentPermission $permission, ArticleStatus $to): void {
    ($this->given)($from);
    $permission === null ?: $this->kit->grant($permission);

    $this->kit->make($handler)->handle($command);

    expect(($this->status)())->toBe($to)
        ->and($this->kit->events->dispatched)->toHaveCount(1);
})->with(workflow_handlers());

it('refuses without the matching permission', function (string $handler, object $command, ArticleStatus $from, ?ContentPermission $permission): void {
    ($this->given)($from);
    $this->kit->grant(...array_filter(ContentPermission::cases(), static fn (ContentPermission $p): bool => $p !== $permission && $p !== ContentPermission::EditAnyArticle));

    expect(fn () => $this->kit->make($handler)->handle($command))->toThrow(NotAuthorized::class);
    expect(($this->status)())->toBe($from)
        ->and($this->kit->events->dispatched)->toBe([]);
})->with(array_filter(workflow_handlers(), static fn (array $case): bool => $case[3] !== null));

it('lets only the writer or an editor submit or withdraw', function (): void {
    ($this->given)(ArticleStatus::Draft);
    $stranger = new SubmitArticleForReviewCommand(99, 1);

    expect(fn () => $this->kit->make(SubmitArticleForReviewHandler::class)->handle($stranger))->toThrow(NotAuthorized::class);

    $this->kit->grant(ContentPermission::EditAnyArticle);
    $this->kit->make(SubmitArticleForReviewHandler::class)->handle($stranger);
    expect(($this->status)())->toBe(ArticleStatus::InReview);
});

it('stops an editor from approving their own article', function (): void {
    ($this->given)(ArticleStatus::InReview);
    $this->kit->grant(ContentPermission::ReviewArticles);

    expect(fn () => $this->kit->make(ApproveArticleHandler::class)->handle(new ApproveArticleCommand(Articles::WRITER, 1)))
        ->toThrow(ArticleWorkflowViolation::class);
    expect($this->kit->events->ofType(ArticleApproved::class))->toBe([]);
});

it('surfaces illegal transitions from the domain', function (): void {
    ($this->given)(ArticleStatus::Draft);
    $this->kit->grant(ContentPermission::PublishArticles);

    expect(fn () => $this->kit->make(PublishArticleHandler::class)->handle(new PublishArticleCommand(Articles::CHIEF, 1)))
        ->toThrow(IllegalArticleTransition::class);
    expect($this->kit->events->ofType(ArticlePublished::class))->toBe([]);
});

it('marks and clears breaking news and promotes, with the promote permission', function (): void {
    ($this->given)(ArticleStatus::Published);
    $until = $this->kit->clock->now()->modify('+2 hours');
    $mark = new MarkArticleBreakingCommand(Articles::CHIEF, 1, $until);

    expect(fn () => $this->kit->make(MarkArticleBreakingHandler::class)->handle($mark))->toThrow(NotAuthorized::class);

    $this->kit->grant(ContentPermission::PromoteArticles);
    $this->kit->make(MarkArticleBreakingHandler::class)->handle($mark);
    $this->kit->make(PromoteArticleHandler::class)->handle(new PromoteArticleCommand(Articles::CHIEF, 1, true, 7));

    $article = $this->kit->articles->get(ArticleId::fromInt(1));
    expect($article->isBreakingAt($this->kit->clock->now()))->toBeTrue()
        ->and($article->breakingUntil())->toEqual($until)
        ->and($article->isFeatured())->toBeTrue()
        ->and($article->homepagePriority())->toBe(7);

    $this->kit->make(UnmarkArticleBreakingHandler::class)->handle(new UnmarkArticleBreakingCommand(Articles::CHIEF, 1));
    expect($this->kit->articles->get(ArticleId::fromInt(1))->isBreaking())->toBeFalse();
});
