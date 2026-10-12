<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Port;

use Elaf\Publishing\Domain\Issue\IssueId;

/**
 * How many articles (in any state) point at an issue.
 */
interface IssueArticleLinks
{
    public function count(IssueId $id): int;
}
