<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\RestoreArticleRevision;

final readonly class RestoreArticleRevisionCommand
{
    public function __construct(
        public int $actorId,
        public int $revisionId,
    ) {}
}
