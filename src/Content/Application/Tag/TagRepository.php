<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Tag;

use Elaf\Content\Domain\Tag\TagId;
use Elaf\Shared\Domain\Text\Slug;

interface TagRepository
{
    public function create(string $name, Slug $slug): TagId;

    public function find(TagId $id): ?Tag;

    public function findBySlug(Slug $slug): ?Tag;
}
