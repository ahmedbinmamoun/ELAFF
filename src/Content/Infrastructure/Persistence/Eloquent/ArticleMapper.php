<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Eloquent;

use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleBody;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleSource;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Article\FeaturedImage;
use Elaf\Content\Domain\Article\IssuePlacement;
use Elaf\Content\Domain\Article\SeriesPlacement;
use Elaf\Content\Domain\Article\Title;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Shared\Domain\Text\Slug;
use LogicException;

final class ArticleMapper
{
    public function toDomain(ArticleModel $model): Article
    {
        $content = new ArticleContent(
            title: new Title($model->title),
            slug: Slug::fromString($model->slug),
            body: ArticleBody::fromSanitizedHtml($model->body),
            source: new ArticleSource($model->source_name, $model->source_url),
            categoryId: CategoryId::fromInt($model->category_id),
            authorIds: array_values($model->authors->map(static fn ($a): AuthorId => AuthorId::fromInt($a->id))->all()),
            tagIds: array_values($model->tags->map(static fn ($t): TagId => TagId::fromInt($t->id))->all()),
            kicker: $model->kicker,
            subtitle: $model->subtitle,
            dateline: $model->dateline,
            excerpt: $model->excerpt,
            featuredImage: $model->featured_image_path === null ? null
                : new FeaturedImage($model->featured_image_path, $model->featured_image_caption, $model->featured_image_credit),
            series: $model->series_id === null || $model->series_part === null ? null
                : new SeriesPlacement(SeriesId::fromInt($model->series_id), $model->series_part),
            seoTitle: $model->seo_title,
            seoDescription: $model->seo_description,
            canonicalUrl: $model->canonical_url,
        );

        return Article::reconstitute(
            id: ArticleId::fromInt($model->id),
            origin: ArticleOrigin::from($model->origin),
            createdBy: UserId::fromInt($model->created_by),
            createdAt: LocalTime::fromColumn($model->created_at) ?? throw new LogicException('Article without created_at.'),
            updatedBy: UserId::fromInt($model->updated_by),
            content: $content,
            status: ArticleStatus::from($model->status),
            publishedAt: LocalTime::fromColumn($model->published_at),
            scheduledFor: LocalTime::fromColumn($model->scheduled_for),
            submittedAt: LocalTime::fromColumn($model->submitted_at),
            reviewedBy: $model->reviewed_by === null ? null : UserId::fromInt($model->reviewed_by),
            returnNote: $model->return_note,
            breaking: $model->is_breaking,
            breakingUntil: LocalTime::fromColumn($model->breaking_until),
            featured: $model->is_featured,
            homepagePriority: $model->homepage_priority,
            issue: $model->issue_id === null || $model->issue_page === null ? null
                : new IssuePlacement(IssueId::fromInt($model->issue_id), $model->issue_page),
        );
    }

    /**
     * Column values; relations (authors, tags) are synced by the repository.
     *
     * @return array<string, mixed>
     */
    public function toColumns(Article $article): array
    {
        $content = $article->content();

        return [
            'origin' => $article->origin()->value,
            'category_id' => $content->categoryId->value,
            'series_id' => $content->series?->seriesId->value,
            'series_part' => $content->series?->part,
            'issue_id' => $article->issue()?->issueId->value,
            'issue_page' => $article->issue()?->page,
            'kicker' => $content->kicker,
            'title' => $content->title->value,
            'subtitle' => $content->subtitle,
            'slug' => $content->slug->value,
            'dateline' => $content->dateline,
            'excerpt' => $content->excerpt,
            'body' => $content->body->html,
            'featured_image_path' => $content->featuredImage?->path,
            'featured_image_caption' => $content->featuredImage?->caption,
            'featured_image_credit' => $content->featuredImage?->credit,
            'source_name' => $content->source->name,
            'source_url' => $content->source->url,
            'status' => $article->status()->value,
            'published_at' => LocalTime::toColumn($article->publishedAt()),
            'scheduled_for' => LocalTime::toColumn($article->scheduledFor()),
            'is_breaking' => $article->isBreaking(),
            'breaking_until' => LocalTime::toColumn($article->breakingUntil()),
            'is_featured' => $article->isFeatured(),
            'homepage_priority' => $article->homepagePriority(),
            'reading_minutes' => $content->body->readingMinutes(),
            'seo_title' => $content->seoTitle,
            'seo_description' => $content->seoDescription,
            'canonical_url' => $content->canonicalUrl,
            'created_by' => $article->createdBy()->value,
            'updated_by' => $article->updatedBy()->value,
            'reviewed_by' => $article->reviewedBy()?->value,
            'submitted_at' => LocalTime::toColumn($article->submittedAt()),
            'return_note' => $article->returnNote(),
        ];
    }
}
