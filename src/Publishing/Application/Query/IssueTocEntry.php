<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Query;

/**
 * A published article that appeared in the issue (FR-ISS-03).
 */
final readonly class IssueTocEntry
{
    public function __construct(
        public int $page,
        public int $articleId,
        public string $slug,
        public string $title,
        public ?string $kicker,
        public ?string $section,
    ) {}
}
