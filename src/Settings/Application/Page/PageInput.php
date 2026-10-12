<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page;

/**
 * Raw form values for a static page. A null slug is derived from the title.
 */
final readonly class PageInput
{
    public function __construct(
        public string $title,
        public ?string $slug = null,
        public string $body = '',
        public bool $isPublished = true,
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
    ) {}
}
