<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article;

/**
 * Raw editor form values (FR-ART-01). The body is unsanitized HTML; a blank
 * slug is derived from the title; tags are typed names (FR-TAX-02).
 */
final readonly class ArticleInput
{
    /**
     * @param  list<int>  $authorIds  in byline order
     * @param  list<string>  $tagNames
     */
    public function __construct(
        public string $title,
        public string $body,
        public string $sourceName,
        public int $categoryId,
        public array $authorIds = [],
        public array $tagNames = [],
        public ?string $slug = null,
        public ?string $kicker = null,
        public ?string $subtitle = null,
        public ?string $dateline = null,
        public ?string $excerpt = null,
        public ?string $sourceUrl = null,
        public ?string $featuredImagePath = null,
        public ?string $featuredImageCaption = null,
        public ?string $featuredImageCredit = null,
        public ?int $seriesId = null,
        public ?int $seriesPart = null,
        public ?string $seoTitle = null,
        public ?string $seoDescription = null,
        public ?string $canonicalUrl = null,
    ) {}

    /**
     * @param  list<int>  $authorIds
     */
    public function withAuthorIds(array $authorIds): self
    {
        return new self(
            $this->title, $this->body, $this->sourceName, $this->categoryId, $authorIds, $this->tagNames,
            $this->slug, $this->kicker, $this->subtitle, $this->dateline, $this->excerpt, $this->sourceUrl,
            $this->featuredImagePath, $this->featuredImageCaption, $this->featuredImageCredit,
            $this->seriesId, $this->seriesPart, $this->seoTitle, $this->seoDescription, $this->canonicalUrl,
        );
    }
}
