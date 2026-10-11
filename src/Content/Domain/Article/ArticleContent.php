<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article;

use Elaf\Content\Domain\Article\Exception\InvalidArticleContent;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Shared\Domain\Text\OptionalText;
use Elaf\Shared\Domain\Text\Slug;

/**
 * Everything an editor can change in one save (FR-ART-01). It is also the
 * revision snapshot (FR-ART-08), hence toArray()/fromArray().
 */
final readonly class ArticleContent
{
    public ?string $kicker;

    public ?string $subtitle;

    public ?string $dateline;

    public ?string $excerpt;

    public ?string $seoTitle;

    public ?string $seoDescription;

    public ?string $canonicalUrl;

    /** Section of the printed paper the article appeared in, e.g. "حوار". */
    public ?string $printSection;

    /** @var list<AuthorId> */
    public array $authorIds;

    /** @var list<TagId> */
    public array $tagIds;

    /**
     * @param  list<AuthorId>  $authorIds  in byline order
     * @param  list<TagId>  $tagIds  a set; stored sorted by id
     */
    public function __construct(
        public Title $title,
        public Slug $slug,
        public ArticleBody $body,
        public ArticleSource $source,
        public CategoryId $categoryId,
        array $authorIds = [],
        array $tagIds = [],
        ?string $kicker = null,
        ?string $subtitle = null,
        ?string $dateline = null,
        ?string $excerpt = null,
        public ?FeaturedImage $featuredImage = null,
        public ?SeriesPlacement $series = null,
        ?string $seoTitle = null,
        ?string $seoDescription = null,
        ?string $canonicalUrl = null,
        ?string $printSection = null,
    ) {
        $this->authorIds = self::unique('authors', $authorIds);
        $tagIds = self::unique('tags', $tagIds);
        usort($tagIds, static fn (TagId $a, TagId $b): int => $a->value <=> $b->value);
        $this->tagIds = $tagIds;
        $this->kicker = self::text('kicker', $kicker, 255);
        $this->subtitle = self::text('subtitle', $subtitle, 500);
        $this->dateline = self::text('dateline', $dateline, 100);
        $this->excerpt = self::text('excerpt', $excerpt, 1000);
        $this->seoTitle = self::text('SEO title', $seoTitle, 255);
        $this->seoDescription = self::text('SEO description', $seoDescription, 500);
        $this->canonicalUrl = self::text('canonical URL', $canonicalUrl, 2048);
        $this->printSection = self::text('print section', $printSection, 60);

        if ($this->canonicalUrl !== null && ! OptionalText::isHttpUrl($this->canonicalUrl)) {
            throw InvalidArticleContent::invalidUrl('canonical URL', $this->canonicalUrl);
        }
    }

    /**
     * Fields still missing before the article may go live (PRD §6.1).
     *
     * @return list<string>
     */
    public function missingForPublication(): array
    {
        $missing = [];

        if ($this->body->isEmpty() && $this->featuredImage === null) {
            $missing[] = 'body or featured image';
        }

        if ($this->authorIds === []) {
            $missing[] = 'author';
        }

        return $missing;
    }

    public function equals(self $other): bool
    {
        return $this->toArray() === $other->toArray();
    }

    /**
     * Field-level diff for the audit log: [field => [old, new]].
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function diff(self $previous): array
    {
        $before = $previous->toArray();
        $changes = [];

        foreach ($this->toArray() as $field => $value) {
            if ($before[$field] !== $value) {
                $changes[$field] = [$before[$field], $value];
            }
        }

        return $changes;
    }

    /**
     * @return array<string, scalar|list<int>|array<string, ?string>|null>
     */
    public function toArray(): array
    {
        return [
            'kicker' => $this->kicker,
            'title' => $this->title->value,
            'subtitle' => $this->subtitle,
            'slug' => $this->slug->value,
            'dateline' => $this->dateline,
            'excerpt' => $this->excerpt,
            'body' => $this->body->html,
            'featured_image' => $this->featuredImage === null ? null : [
                'path' => $this->featuredImage->path,
                'caption' => $this->featuredImage->caption,
                'credit' => $this->featuredImage->credit,
            ],
            'source_name' => $this->source->name,
            'source_url' => $this->source->url,
            'category_id' => $this->categoryId->value,
            'author_ids' => array_map(static fn (AuthorId $id): int => $id->value, $this->authorIds),
            'tag_ids' => array_map(static fn (TagId $id): int => $id->value, $this->tagIds),
            'series_id' => $this->series?->seriesId->value,
            'series_part' => $this->series?->part,
            'seo_title' => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'canonical_url' => $this->canonicalUrl,
            'print_section' => $this->printSection,
        ];
    }

    /**
     * @param  array<mixed>  $data  output of toArray(), e.g. a stored revision
     */
    public static function fromArray(array $data): self
    {
        $image = $data['featured_image'] ?? null;
        $seriesId = self::optionalInt($data, 'series_id');

        return new self(
            title: new Title(self::string($data, 'title')),
            slug: Slug::fromString(self::string($data, 'slug')),
            body: ArticleBody::fromSanitizedHtml(self::string($data, 'body')),
            source: new ArticleSource(self::string($data, 'source_name'), self::optionalString($data, 'source_url')),
            categoryId: CategoryId::fromInt(self::int($data, 'category_id')),
            authorIds: array_map(AuthorId::fromInt(...), self::ints($data, 'author_ids')),
            tagIds: array_map(TagId::fromInt(...), self::ints($data, 'tag_ids')),
            kicker: self::optionalString($data, 'kicker'),
            subtitle: self::optionalString($data, 'subtitle'),
            dateline: self::optionalString($data, 'dateline'),
            excerpt: self::optionalString($data, 'excerpt'),
            featuredImage: is_array($image) ? new FeaturedImage(
                self::string($image, 'path'),
                self::optionalString($image, 'caption'),
                self::optionalString($image, 'credit'),
            ) : null,
            series: $seriesId === null ? null : new SeriesPlacement(SeriesId::fromInt($seriesId), self::int($data, 'series_part')),
            seoTitle: self::optionalString($data, 'seo_title'),
            seoDescription: self::optionalString($data, 'seo_description'),
            canonicalUrl: self::optionalString($data, 'canonical_url'),
            printSection: self::optionalString($data, 'print_section'),
        );
    }

    /**
     * @template T of AuthorId|TagId
     *
     * @param  list<T>  $ids
     * @return list<T>
     */
    private static function unique(string $field, array $ids): array
    {
        $values = array_map(static fn (AuthorId|TagId $id): int => $id->value, $ids);

        if (count($values) !== count(array_unique($values))) {
            throw InvalidArticleContent::duplicate($field);
        }

        return $ids;
    }

    private static function text(string $field, ?string $value, int $max): ?string
    {
        $value = OptionalText::normalize($value);

        if ($value !== null && mb_strlen($value) > $max) {
            throw InvalidArticleContent::tooLong($field, $max);
        }

        return $value;
    }

    /** @param array<mixed> $data */
    private static function string(array $data, string $key): string
    {
        return is_string($data[$key] ?? null) ? $data[$key] : throw InvalidArticleContent::malformedSnapshot("{$key} must be a string");
    }

    /** @param array<mixed> $data */
    private static function optionalString(array $data, string $key): ?string
    {
        return ($data[$key] ?? null) === null ? null : self::string($data, $key);
    }

    /** @param array<mixed> $data */
    private static function int(array $data, string $key): int
    {
        return is_int($data[$key] ?? null) ? $data[$key] : throw InvalidArticleContent::malformedSnapshot("{$key} must be an integer");
    }

    /** @param array<mixed> $data */
    private static function optionalInt(array $data, string $key): ?int
    {
        return ($data[$key] ?? null) === null ? null : self::int($data, $key);
    }

    /**
     * @param  array<mixed>  $data
     * @return list<int>
     */
    private static function ints(array $data, string $key): array
    {
        $values = $data[$key] ?? [];

        if (! is_array($values) || ! array_is_list($values)) {
            throw InvalidArticleContent::malformedSnapshot("{$key} must be a list");
        }

        return array_map(static fn (mixed $v): int => is_int($v) ? $v : throw InvalidArticleContent::malformedSnapshot("{$key} must hold integers"), $values);
    }
}
