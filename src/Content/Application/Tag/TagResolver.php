<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Tag;

use Elaf\Content\Domain\Tag\TagId;
use Elaf\Shared\Application\Validation\Fields;
use Elaf\Shared\Application\Validation\InvalidInput;

/**
 * Turns typed tag names into tag ids, creating the missing tags. Two names
 * with the same slug resolve to the same tag.
 */
final readonly class TagResolver
{
    public const MAX_TAGS = 20;

    public function __construct(private TagRepository $tags) {}

    /**
     * @param  list<string>  $names
     * @return list<TagId>
     */
    public function resolve(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            if (trim($name) === '') {
                continue;
            }

            $name = Fields::required('tags', $name, 60);
            $slug = Fields::slug('tags', null, $name);
            $ids[$slug->value] ??= $this->tags->findBySlug($slug)->id ?? $this->tags->create($name, $slug);
        }

        if (count($ids) > self::MAX_TAGS) {
            throw new InvalidInput('tags', sprintf('An article can have at most %d tags.', self::MAX_TAGS));
        }

        return array_values($ids);
    }
}
