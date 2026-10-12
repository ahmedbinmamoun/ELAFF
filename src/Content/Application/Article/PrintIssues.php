<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article;

use Elaf\Publishing\Domain\Issue\IssueId;

/**
 * What Content needs to know about print issues to link an article to one
 * (FR-ART-01). Implemented by the Publishing module.
 */
interface PrintIssues
{
    public function find(IssueId $id): ?PrintIssue;
}
