<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\ScheduleArticle;

use DateTimeImmutable;

final readonly class ScheduleArticleCommand
{
    public function __construct(
        public int $actorId,
        public int $articleId,
        public DateTimeImmutable $publishAt,
    ) {}
}
