<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article;

use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Application\Category\CategoryRepository;
use Elaf\Content\Application\Series\SeriesRepository;
use Elaf\Content\Application\Tag\TagResolver;
use Elaf\Content\Domain\Article\ArticleBody;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleSource;
use Elaf\Content\Domain\Article\FeaturedImage;
use Elaf\Content\Domain\Article\SeriesPlacement;
use Elaf\Content\Domain\Article\Title;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Shared\Application\Html\HtmlSanitizer;
use Elaf\Shared\Application\Html\SanitizerProfile;
use Elaf\Shared\Application\Validation\Fields;
use Elaf\Shared\Application\Validation\InvalidInput;

/**
 * Validates editor input field by field (so errors map to form fields) and
 * builds the domain value object. Creates missing tags, so call it inside
 * the handler's transaction.
 */
final readonly class ArticleContentFactory
{
    public const MAX_BODY_LENGTH = 200_000;

    /** FR-CON-10. */
    public const MAX_CONTRIBUTOR_BODY_LENGTH = 40_000;

    public function __construct(
        private CategoryRepository $categories,
        private AuthorRepository $authors,
        private SeriesRepository $series,
        private TagResolver $tags,
        private HtmlSanitizer $sanitizer,
    ) {}

    public function create(ArticleInput $input, ArticleOrigin $origin): ArticleContent
    {
        $title = Fields::required('title', $input->title, Title::MAX_LENGTH);

        return new ArticleContent(
            title: new Title($title),
            slug: Fields::slug('slug', $input->slug, $title),
            body: $this->body($input->body, $origin),
            source: new ArticleSource(
                Fields::required('source_name', $input->sourceName, ArticleSource::MAX_LENGTH),
                Fields::httpUrl('source_url', $input->sourceUrl),
            ),
            categoryId: $this->category($input->categoryId, $origin),
            authorIds: $this->authorIds($input->authorIds),
            tagIds: $this->tags->resolve($input->tagNames),
            kicker: Fields::optional('kicker', $input->kicker, 255),
            subtitle: Fields::optional('subtitle', $input->subtitle, 500),
            dateline: Fields::optional('dateline', $input->dateline, 100),
            excerpt: Fields::optional('excerpt', $input->excerpt, 1000),
            featuredImage: $this->featuredImage($input),
            series: $this->seriesPlacement($input->seriesId, $input->seriesPart),
            seoTitle: Fields::optional('seo_title', $input->seoTitle, 255),
            seoDescription: Fields::optional('seo_description', $input->seoDescription, 500),
            canonicalUrl: Fields::httpUrl('canonical_url', $input->canonicalUrl),
            printSection: Fields::optional('print_section', $input->printSection, 60),
        );
    }

    private function body(string $html, ArticleOrigin $origin): ArticleBody
    {
        $max = $origin === ArticleOrigin::Contributor ? self::MAX_CONTRIBUTOR_BODY_LENGTH : self::MAX_BODY_LENGTH;

        if (mb_strlen($html) > $max) {
            throw InvalidInput::tooLong('body', $max);
        }

        $profile = $origin === ArticleOrigin::Contributor ? SanitizerProfile::Contributor : SanitizerProfile::Staff;

        return ArticleBody::fromSanitizedHtml($this->sanitizer->sanitize($html, $profile));
    }

    private function category(int $id, ArticleOrigin $origin): CategoryId
    {
        $category = $id > 0 ? $this->categories->find(CategoryId::fromInt($id)) : null;

        if ($category === null) {
            throw InvalidInput::unknownReference('category_id', $id);
        }

        if ($origin === ArticleOrigin::Contributor && ! $category->data->isOpenToContributors) {
            throw InvalidInput::because('category_id', 'category_closed_to_contributors', 'This category is not open to contributors.');
        }

        return $category->id;
    }

    /**
     * @param  list<int>  $ids
     * @return list<AuthorId>
     */
    private function authorIds(array $ids): array
    {
        $ids = array_values(array_unique($ids));

        foreach ($ids as $id) {
            if ($id < 1 || $this->authors->find(AuthorId::fromInt($id)) === null) {
                throw InvalidInput::unknownReference('author_ids', $id);
            }
        }

        return array_map(AuthorId::fromInt(...), $ids);
    }

    private function featuredImage(ArticleInput $input): ?FeaturedImage
    {
        $path = Fields::optional('featured_image_path', $input->featuredImagePath, 255);

        return $path === null ? null : new FeaturedImage(
            $path,
            Fields::optional('featured_image_caption', $input->featuredImageCaption, 500),
            Fields::optional('featured_image_credit', $input->featuredImageCredit, 255),
        );
    }

    private function seriesPlacement(?int $seriesId, ?int $part): ?SeriesPlacement
    {
        if ($seriesId === null) {
            return null;
        }

        if ($seriesId < 1 || $this->series->find(SeriesId::fromInt($seriesId)) === null) {
            throw InvalidInput::unknownReference('series_id', $seriesId);
        }

        if ($part === null || $part < 1) {
            throw InvalidInput::because('series_part', 'series_part_required', 'A series article needs a part number of 1 or more.');
        }

        return new SeriesPlacement(SeriesId::fromInt($seriesId), $part);
    }
}
