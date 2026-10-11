<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

final readonly class ArticleLink
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
    ) {}
}
