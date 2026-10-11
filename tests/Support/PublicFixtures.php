<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleBody;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleRepository;
use Elaf\Content\Domain\Article\ArticleSource;
use Elaf\Content\Domain\Article\FeaturedImage;
use Elaf\Content\Domain\Article\SeriesPlacement;
use Elaf\Content\Domain\Article\Title;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Text\Slug;
use Tests\Support\Fakes\FrozenClock;

/**
 * Published content for public-site tests, created through the domain with a
 * controllable clock (publication times in order).
 */
final class PublicFixtures
{
    private FrozenClock $clock;

    private UserId $writer;

    private UserId $editor;

    private UserId $chief;

    public function __construct(string $start = '2026-03-01 08:00:00')
    {
        $this->clock = new FrozenClock($start);
        $this->writer = UserId::fromInt(AdminFixtures::user(Role::Writer)->id);
        $this->editor = UserId::fromInt(AdminFixtures::user(Role::Editor)->id);
        $this->chief = UserId::fromInt(AdminFixtures::user(Role::EditorInChief)->id);
    }

    /**
     * @param  list<int>  $authors
     * @param  list<int>  $tags
     * @param  array{0: int, 1: int}|null  $series  [series id, part]
     * @param  callable(Article, FrozenClock, UserId): void|null  $then  more domain calls after publishing
     */
    public function published(
        string $title,
        int $category,
        array $authors,
        array $tags = [],
        ?array $series = null,
        ?string $image = null,
        ArticleOrigin $origin = ArticleOrigin::Staff,
        ?callable $then = null,
        bool $publish = true,
    ): ArticleModel {
        $this->clock->advance('PT1H');
        $repository = app(ArticleRepository::class);

        $article = Article::draft($repository->nextId(), $origin, $this->writer, new ArticleContent(
            title: new Title($title),
            slug: Slug::fromText($title),
            body: ArticleBody::fromSanitizedHtml('<p>نص '.$title.'</p><table><tr><td>1</td></tr></table>'),
            source: new ArticleSource('إيلاف'),
            categoryId: CategoryId::fromInt($category),
            authorIds: array_map(AuthorId::fromInt(...), $authors),
            tagIds: array_map(TagId::fromInt(...), $tags),
            kicker: 'تمهيد '.$title,
            excerpt: 'مقتطف '.$title,
            featuredImage: $image === null ? null : new FeaturedImage($image, 'تعليق', 'إيلاف'),
            series: $series === null ? null : new SeriesPlacement(SeriesId::fromInt($series[0]), $series[1]),
        ), $this->clock);

        $article->submitForReview($this->writer, $this->clock);
        $article->approve($this->editor, $this->clock);

        if ($publish) {
            $article->publish($this->chief, $this->clock);
        }

        if ($then !== null) {
            $then($article, $this->clock, $this->chief);
        }

        $article->releaseEvents();
        $repository->save($article);

        return ArticleModel::query()->findOrFail($article->id()->value);
    }

    public function clock(): FrozenClock
    {
        return $this->clock;
    }

    public static function writerUser(): User
    {
        return AdminFixtures::user(Role::Writer);
    }
}
