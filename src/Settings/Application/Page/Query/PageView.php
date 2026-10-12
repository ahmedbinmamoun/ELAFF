<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page\Query;

use DateTimeImmutable;

final readonly class PageView
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $bodyHtml,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public DateTimeImmutable $updatedAt,
    ) {}
}
