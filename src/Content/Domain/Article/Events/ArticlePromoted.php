<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article\Events;

use DateTimeImmutable;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;

final readonly class ArticlePromoted extends ArticleEvent
{
    public function __construct(
        ArticleId $articleId,
        UserId $actorId,
        DateTimeImmutable $at,
        public bool $featured,
        public int $homepagePriority,
    ) {
        parent::__construct($articleId, $actorId, $at);
    }

    public function details(): array
    {
        return ['featured' => $this->featured, 'homepage_priority' => $this->homepagePriority];
    }
}
