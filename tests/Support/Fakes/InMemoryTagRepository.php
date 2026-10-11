<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Content\Application\Tag\Tag;
use Elaf\Content\Application\Tag\TagQueries;
use Elaf\Content\Application\Tag\TagRepository;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Shared\Domain\Text\Slug;

final class InMemoryTagRepository implements TagQueries, TagRepository
{
    /** @var array<int, Tag> */
    private array $tags = [];

    private int $lastId = 0;

    public function create(string $name, Slug $slug): TagId
    {
        $id = TagId::fromInt(++$this->lastId);
        $this->tags[$id->value] = new Tag($id, $name, $slug);

        return $id;
    }

    public function find(TagId $id): ?Tag
    {
        return $this->tags[$id->value] ?? null;
    }

    public function findBySlug(Slug $slug): ?Tag
    {
        foreach ($this->tags as $tag) {
            if ($tag->slug->equals($slug)) {
                return $tag;
            }
        }

        return null;
    }

    public function rename(TagId $id, string $name, Slug $slug): void
    {
        $this->tags[$id->value] = new Tag($id, $name, $slug);
    }

    public function delete(TagId $id): void
    {
        unset($this->tags[$id->value]);
    }

    public function suggest(string $prefix, int $limit = 10): array
    {
        $prefix = mb_strtolower(trim($prefix));

        if ($prefix === '') {
            return [];
        }

        $matches = array_filter($this->tags, static fn (Tag $t): bool => str_starts_with(mb_strtolower($t->name), $prefix));
        usort($matches, static fn (Tag $a, Tag $b): int => strcmp($a->name, $b->name));

        return array_slice($matches, 0, $limit);
    }
}
