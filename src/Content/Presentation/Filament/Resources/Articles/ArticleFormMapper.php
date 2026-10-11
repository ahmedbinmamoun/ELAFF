<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Filament\Resources\Articles;

use Elaf\Content\Application\Article\ArticleInput;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\TagModel;
use Elaf\Content\Presentation\Filament\Editor\FigureBlockConverter;
use Elaf\Shared\Presentation\Filament\FormData;

/**
 * Form state ⇄ use-case input. Form field names match the use-case field
 * names so InvalidInput errors land on the right field.
 */
final class ArticleFormMapper
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function toInput(array $data): ArticleInput
    {
        return new ArticleInput(
            title: FormData::string($data, 'title'),
            body: FigureBlockConverter::toStorage(FormData::string($data, 'body')),
            sourceName: FormData::string($data, 'source_name'),
            categoryId: FormData::int($data, 'category_id') ?? 0,
            authorIds: FormData::ints($data, 'author_ids'),
            tagNames: FormData::strings($data, 'tags'),
            slug: FormData::text($data, 'slug'),
            kicker: FormData::text($data, 'kicker'),
            subtitle: FormData::text($data, 'subtitle'),
            dateline: FormData::text($data, 'dateline'),
            excerpt: FormData::text($data, 'excerpt'),
            sourceUrl: FormData::text($data, 'source_url'),
            featuredImagePath: FormData::text($data, 'featured_image_path'),
            featuredImageCaption: FormData::text($data, 'featured_image_caption'),
            featuredImageCredit: FormData::text($data, 'featured_image_credit'),
            seriesId: FormData::int($data, 'series_id'),
            seriesPart: FormData::int($data, 'series_part'),
            seoTitle: FormData::text($data, 'seo_title'),
            seoDescription: FormData::text($data, 'seo_description'),
            canonicalUrl: FormData::text($data, 'canonical_url'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function fill(ArticleModel $article): array
    {
        return [
            'kicker' => $article->kicker,
            'title' => $article->title,
            'subtitle' => $article->subtitle,
            'slug' => $article->slug,
            'dateline' => $article->dateline,
            'excerpt' => $article->excerpt,
            'body' => FigureBlockConverter::toEditor($article->body),
            'category_id' => $article->category_id,
            'author_ids' => $article->authors->map(static fn (AuthorModel $a): int => $a->id)->values()->all(),
            'tags' => $article->tags->sortBy('name')->map(static fn (TagModel $t): string => $t->name)->values()->all(),
            'source_name' => $article->source_name,
            'source_url' => $article->source_url,
            'featured_image_path' => $article->featured_image_path,
            'featured_image_caption' => $article->featured_image_caption,
            'featured_image_credit' => $article->featured_image_credit,
            'series_id' => $article->series_id,
            'series_part' => $article->series_part,
            'seo_title' => $article->seo_title,
            'seo_description' => $article->seo_description,
            'canonical_url' => $article->canonical_url,
        ];
    }
}
