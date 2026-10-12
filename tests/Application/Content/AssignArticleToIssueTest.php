<?php

declare(strict_types=1);

use Elaf\Content\Application\Article\AssignArticleToIssue\AssignArticleToIssueCommand;
use Elaf\Content\Application\Article\AssignArticleToIssue\AssignArticleToIssueHandler;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftCommand;
use Elaf\Content\Application\Article\CreateArticleDraft\CreateArticleDraftHandler;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\Events\ArticleAssignedToIssue;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Tests\Support\ContentKit;

beforeEach(function (): void {
    $this->kit = (new ContentKit)->grant(ContentPermission::CreateArticles, ContentPermission::EditAnyArticle);
    $this->articleId = $this->kit->make(CreateArticleDraftHandler::class)
        ->handle(new CreateArticleDraftCommand(1, ArticleOrigin::Staff, $this->kit->input()))->value;
    $this->kit->printIssues->add(5, 120, 12);
    $this->kit->printIssues->add(6, 121, null);
    $this->assign = fn (?int $issue, ?int $page) => $this->kit->make(AssignArticleToIssueHandler::class)
        ->handle(new AssignArticleToIssueCommand(2, $this->articleId, $issue, $page));
    $this->reason = static function (callable $work): string {
        try {
            $work();
        } catch (InvalidInput $e) {
            return $e->field.':'.$e->reason;
        }

        return 'none';
    };
});

it('links an article to an issue page and unlinks it (FR-ART-01)', function (): void {
    ($this->assign)(5, 3);
    $placement = $this->kit->articles->get(ArticleId::fromInt($this->articleId))->issue();

    expect($placement?->issueId->value)->toBe(5)
        ->and($placement?->page)->toBe(3)
        ->and($this->kit->events->ofType(ArticleAssignedToIssue::class))->toHaveCount(1);

    ($this->assign)(null, null);
    expect($this->kit->articles->get(ArticleId::fromInt($this->articleId))->issue())->toBeNull();
});

it('checks the issue and the page', function (?int $issue, ?int $page, string $expected): void {
    expect(($this->reason)(fn () => ($this->assign)($issue, $page)))->toBe($expected);
})->with([
    'unknown issue' => [99, 1, 'issue_id:unknown_reference'],
    'no page' => [5, null, 'issue_page:page_not_positive'],
    'page 0' => [5, 0, 'issue_page:page_not_positive'],
    'beyond the last page' => [5, 13, 'issue_page:page_beyond_issue'],
    'last page' => [5, 12, 'none'],
    'not processed yet: any page' => [6, 40, 'none'],
]);

it('is for editors and above', function (): void {
    $this->kit->authorizer->revoke(ContentPermission::EditAnyArticle->value);

    expect(fn () => ($this->assign)(5, 1))->toThrow(NotAuthorized::class);
});
