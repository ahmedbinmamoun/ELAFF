<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Author\CreateAuthor;

use Elaf\Content\Application\Author\AuthorInput;

final readonly class CreateAuthorCommand
{
    public function __construct(public AuthorInput $author) {}
}
