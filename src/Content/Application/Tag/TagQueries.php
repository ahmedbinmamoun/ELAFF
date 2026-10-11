<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Tag;

interface TagQueries
{
    /**
     * Autocomplete: tags whose name starts with $prefix, alphabetical.
     *
     * @return list<Tag>
     */
    public function suggest(string $prefix, int $limit = 10): array;
}
