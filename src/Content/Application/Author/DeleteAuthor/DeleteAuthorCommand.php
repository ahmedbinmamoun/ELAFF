<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Author\DeleteAuthor;

final readonly class DeleteAuthorCommand
{
    public function __construct(public int $authorId) {}
}
