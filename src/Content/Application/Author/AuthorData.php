<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Author;

use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Text\Slug;

/**
 * Validated author profile (FR-AUT-01). Lightweight model, PRD §6.5.
 */
final readonly class AuthorData
{
    /**
     * @param  array<string, string>  $socialLinks  network key => http(s) URL
     */
    public function __construct(
        public string $name,
        public Slug $slug,
        public ?string $title = null,
        public ?string $bio = null,
        public ?string $photoPath = null,
        public bool $isColumnist = false,
        public ?UserId $userId = null,
        public array $socialLinks = [],
    ) {}
}
