<?php

declare(strict_types=1);

namespace Elaf\Publishing\Infrastructure\Content;

use Elaf\Content\Application\Article\PrintIssue;
use Elaf\Content\Application\Article\PrintIssues;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Infrastructure\Persistence\IssueModel;

/**
 * Answers Content's questions about print issues.
 */
final class PublishingPrintIssues implements PrintIssues
{
    public function find(IssueId $id): ?PrintIssue
    {
        $issue = IssueModel::query()->find($id->value, ['id', 'number', 'page_count']);

        return $issue === null ? null : new PrintIssue($issue->number, $issue->page_count);
    }
}
