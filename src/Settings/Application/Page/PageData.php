<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page;

use Elaf\Shared\Domain\Text\Slug;

/**
 * Validated static page (FR-PAG-01). Lightweight model, PRD §6.5. The body
 * is sanitized HTML.
 */
final readonly class PageData
{
    public function __construct(
        public string $title,
        public Slug $slug,
        public string $body,
        public bool $isPublished,
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
    ) {}
}
