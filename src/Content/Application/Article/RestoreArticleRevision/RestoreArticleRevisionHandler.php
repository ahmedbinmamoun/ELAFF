<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\RestoreArticleRevision;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Application\Article\Revision\ArticleRevisions;
use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Application\Category\CategoryRepository;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Application\Series\SeriesRepository;
use Elaf\Content\Application\Tag\TagRepository;
use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-08: puts an earlier snapshot back. The restore is itself a new
 * revision, so it can be undone the same way. Tags, authors and series
 * deleted since the snapshot are left out; a deleted category blocks it.
 */
final readonly class RestoreArticleRevisionHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleRevisions $revisions,
        private CategoryRepository $categories,
        private AuthorRepository $authors,
        private SeriesRepository $series,
        private TagRepository $tags,
        private ArticleAccess $access,
        private Clock $clock,
    ) {}

    public function handle(RestoreArticleRevisionCommand $command): void
    {
        $this->access->require(ContentPermission::EditAnyArticle);
        $actor = UserId::fromInt($command->actorId);
        $revision = $this->revisions->get($command->revisionId);
        $article = $this->articles->load($revision->articleId);
        $content = $this->withExistingReferences($revision->content);

        if ($article->content()->equals($content)) {
            return;
        }

        $article->revise($content, $actor, $this->clock);
        $this->articles->persist($article, fn (Article $saved) => $this->revisions->record(
            $saved->id(), $actor, $saved->content(), $this->clock->now(),
        ));
    }

    private function withExistingReferences(ArticleContent $c): ArticleContent
    {
        if ($this->categories->find($c->categoryId) === null) {
            throw InvalidInput::because('revision', 'revision_category_missing', 'The category of this revision no longer exists.');
        }

        return new ArticleContent(
            title: $c->title,
            slug: $c->slug,
            body: $c->body,
            source: $c->source,
            categoryId: $c->categoryId,
            authorIds: array_values(array_filter($c->authorIds, fn (AuthorId $id): bool => $this->authors->find($id) !== null)),
            tagIds: array_values(array_filter($c->tagIds, fn (TagId $id): bool => $this->tags->find($id) !== null)),
            kicker: $c->kicker,
            subtitle: $c->subtitle,
            dateline: $c->dateline,
            excerpt: $c->excerpt,
            featuredImage: $c->featuredImage,
            series: $c->series !== null && $this->series->find($c->series->seriesId) !== null ? $c->series : null,
            seoTitle: $c->seoTitle,
            seoDescription: $c->seoDescription,
            canonicalUrl: $c->canonicalUrl,
        );
    }
}
