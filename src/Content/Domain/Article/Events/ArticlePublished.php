<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article\Events;

use DateTimeImmutable;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;

final readonly class ArticlePublished extends ArticleEvent
{
    public function __construct(
        ArticleId $articleId,
        UserId $actorId,
        DateTimeImmutable $at,
        public DateTimeImmutable $publishedAt,
    ) {
        parent::__construct($articleId, $actorId, $at);
    }

    public function details(): array
    {
        return ['published_at' => $this->publishedAt->format(DATE_ATOM)];
    }
}
