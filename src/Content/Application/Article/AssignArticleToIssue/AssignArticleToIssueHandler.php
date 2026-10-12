<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\AssignArticleToIssue;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Application\Article\PrintIssues;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\IssuePlacement;
use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-01, FR-ISS-03: records the print issue and page an article appeared
 * on; the issue page lists it in its table of contents. Editors and above.
 */
final readonly class AssignArticleToIssueHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleAccess $access,
        private PrintIssues $issues,
        private Clock $clock,
    ) {}

    public function handle(AssignArticleToIssueCommand $command): void
    {
        $this->access->require(ContentPermission::EditAnyArticle);
        $article = $this->articles->load(ArticleId::fromInt($command->articleId));

        $article->assignToIssue($this->placement($command), UserId::fromInt($command->actorId), $this->clock);
        $this->articles->persist($article);
    }

    private function placement(AssignArticleToIssueCommand $command): ?IssuePlacement
    {
        if ($command->issueId === null) {
            return null;
        }

        $issue = $command->issueId > 0 ? $this->issues->find(IssueId::fromInt($command->issueId)) : null;

        if ($issue === null) {
            throw InvalidInput::unknownReference('issue_id', $command->issueId);
        }

        $page = $command->page ?? 0;

        if ($page < 1) {
            throw InvalidInput::because('issue_page', 'page_not_positive', 'The page number must be 1 or more.');
        }

        if ($issue->pageCount !== null && $page > $issue->pageCount) {
            throw InvalidInput::because('issue_page', 'page_beyond_issue', sprintf('Issue %d has %d pages.', $issue->number, $issue->pageCount), [
                'number' => $issue->number,
                'pages' => $issue->pageCount,
            ]);
        }

        return new IssuePlacement(IssueId::fromInt($command->issueId), $page);
    }
}
