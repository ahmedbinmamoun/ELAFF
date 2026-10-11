<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Tag\DeleteTag;

final readonly class DeleteTagCommand
{
    public function __construct(public int $tagId) {}
}
