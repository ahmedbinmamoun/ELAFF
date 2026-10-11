<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Tag\RenameTag;

final readonly class RenameTagCommand
{
    public function __construct(
        public int $tagId,
        public string $name,
    ) {}
}
