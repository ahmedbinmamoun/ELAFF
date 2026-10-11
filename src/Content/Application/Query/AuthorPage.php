<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

final readonly class AuthorPage
{
    /**
     * @param  array<string, string>  $socialLinks
     */
    public function __construct(
        public int $id,
        public AuthorRef $author,
        public ?string $bio,
        public array $socialLinks,
        public bool $isColumnist,
    ) {}
}
