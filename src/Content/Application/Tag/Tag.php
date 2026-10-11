<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Tag;

use Elaf\Content\Domain\Tag\TagId;
use Elaf\Shared\Domain\Text\Slug;

/**
 * Free-form tag (FR-TAX-02). Lightweight model, PRD §6.5.
 */
final readonly class Tag
{
    public function __construct(
        public TagId $id,
        public string $name,
        public Slug $slug,
    ) {}
}
