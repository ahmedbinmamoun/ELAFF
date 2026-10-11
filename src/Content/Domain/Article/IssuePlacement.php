<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article;

use Elaf\Content\Domain\Article\Exception\InvalidArticleContent;
use Elaf\Publishing\Domain\Issue\IssueId;

/**
 * Where the article appeared in print (FR-ART-01).
 */
final readonly class IssuePlacement
{
    public function __construct(
        public IssueId $issueId,
        public int $page,
    ) {
        if ($page < 1) {
            throw InvalidArticleContent::notPositive('issue page', $page);
        }
    }

    public function equals(?self $other): bool
    {
        return $other !== null && $other->issueId->equals($this->issueId) && $other->page === $this->page;
    }
}
