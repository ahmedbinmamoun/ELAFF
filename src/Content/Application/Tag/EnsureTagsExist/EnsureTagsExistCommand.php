<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Tag\EnsureTagsExist;

final readonly class EnsureTagsExistCommand
{
    /**
     * @param  list<string>  $names
     */
    public function __construct(public array $names) {}
}
