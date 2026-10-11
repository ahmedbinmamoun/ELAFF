<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article\Events;

use DateTimeImmutable;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\IssuePlacement;
use Elaf\Identity\Domain\UserId;

final readonly class ArticleAssignedToIssue extends ArticleEvent
{
    public function __construct(
        ArticleId $articleId,
        UserId $actorId,
        DateTimeImmutable $at,
        public ?IssuePlacement $placement,
    ) {
        parent::__construct($articleId, $actorId, $at);
    }

    public function details(): array
    {
        return ['issue_id' => $this->placement?->issueId->value, 'issue_page' => $this->placement?->page];
    }
}
