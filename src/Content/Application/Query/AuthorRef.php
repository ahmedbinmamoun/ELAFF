<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

final readonly class AuthorRef
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $title = null,
        public ?string $photoPath = null,
    ) {}
}
