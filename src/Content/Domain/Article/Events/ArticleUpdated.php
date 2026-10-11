<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article\Events;

use DateTimeImmutable;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;

final readonly class ArticleUpdated extends ArticleEvent
{
    /**
     * @param  array<string, array{0: mixed, 1: mixed}>  $changes  field => [old, new]
     */
    public function __construct(
        ArticleId $articleId,
        UserId $actorId,
        DateTimeImmutable $at,
        public array $changes,
    ) {
        parent::__construct($articleId, $actorId, $at);
    }

    public function details(): array
    {
        return ['changes' => $this->changes];
    }
}
