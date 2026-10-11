<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

use DateTimeImmutable;

/**
 * A published article as listed on home, category, author… pages.
 */
final readonly class ArticleCard
{
    /**
     * @param  list<AuthorRef>  $authors  byline order
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public ?string $kicker,
        public ?string $excerpt,
        public DateTimeImmutable $publishedAt,
        public CategoryRef $category,
        public array $authors,
        public ?ImageRef $image,
        public int $readingMinutes,
        public bool $byContributor,
    ) {}

    public function link(): ArticleLink
    {
        return new ArticleLink($this->id, $this->title, $this->slug);
    }
}
