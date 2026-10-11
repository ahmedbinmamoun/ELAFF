<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Author\UpdateAuthor;

use Elaf\Content\Application\Author\AuthorInput;

final readonly class UpdateAuthorCommand
{
    public function __construct(
        public int $authorId,
        public AuthorInput $author,
    ) {}
}
