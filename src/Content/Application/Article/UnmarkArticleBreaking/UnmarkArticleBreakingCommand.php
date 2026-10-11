<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\UnmarkArticleBreaking;

final readonly class UnmarkArticleBreakingCommand
{
    public function __construct(
        public int $actorId,
        public int $articleId,
    ) {}
}
