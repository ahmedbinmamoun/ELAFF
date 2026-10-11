<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article\Events;

use DateTimeImmutable;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;

final readonly class ArticleScheduled extends ArticleEvent
{
    public function __construct(
        ArticleId $articleId,
        UserId $actorId,
        DateTimeImmutable $at,
        public DateTimeImmutable $scheduledFor,
    ) {
        parent::__construct($articleId, $actorId, $at);
    }

    public function details(): array
    {
        return ['scheduled_for' => $this->scheduledFor->format(DATE_ATOM)];
    }
}
