<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\ReturnArticleToWriter;

final readonly class ReturnArticleToWriterCommand
{
    public function __construct(
        public int $actorId,
        public int $articleId,
        public string $note,
    ) {}
}
