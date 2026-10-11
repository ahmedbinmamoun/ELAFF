<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Author;

use Elaf\Content\Domain\Author\AuthorId;

final readonly class Author
{
    public function __construct(
        public AuthorId $id,
        public AuthorData $data,
    ) {}
}
