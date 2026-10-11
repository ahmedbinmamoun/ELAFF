<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\MarkArticleBreaking;

use DateTimeImmutable;

final readonly class MarkArticleBreakingCommand
{
    public function __construct(
        public int $actorId,
        public int $articleId,
        public ?DateTimeImmutable $until = null,
    ) {}
}
