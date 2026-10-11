<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Author;

final readonly class AuthorInput
{
    /**
     * @param  array<string, string>  $socialLinks  network key => URL
     */
    public function __construct(
        public string $name,
        public ?string $slug = null,
        public ?string $title = null,
        public ?string $bio = null,
        public ?string $photoPath = null,
        public bool $isColumnist = false,
        public ?int $userId = null,
        public array $socialLinks = [],
    ) {}
}
