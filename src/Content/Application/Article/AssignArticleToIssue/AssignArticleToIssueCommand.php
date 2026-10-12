<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\AssignArticleToIssue;

/**
 * A null issue removes the link.
 */
final readonly class AssignArticleToIssueCommand
{
    public function __construct(
        public int $actorId,
        public int $articleId,
        public ?int $issueId,
        public ?int $page,
    ) {}
}
