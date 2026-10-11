<?php

declare(strict_types=1);

namespace Tests\Support\Builders;

use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleBody;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleSource;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Article\Title;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Text\Slug;
use Elaf\Shared\Domain\Time\Clock;

/**
 * Builds articles in any workflow state by replaying real domain calls.
 * Users: 1 = writer, 2 = editor, 3 = editor-in-chief.
 */
final class Articles
{
    public const WRITER = 1;

    public const EDITOR = 2;

    public const CHIEF = 3;

    /**
     * @param  list<int>  $authorIds
     * @param  list<int>  $tagIds
     */
    public static function content(
        string $title = 'عنوان المقال التجريبي',
        string $body = '<p>نص تجريبي للمقال</p>',
        int $categoryId = 1,
        array $authorIds = [1],
        array $tagIds = [],
    ): ArticleContent {
        return new ArticleContent(
            title: new Title($title),
            slug: Slug::fromText($title),
            body: ArticleBody::fromSanitizedHtml($body),
            source: new ArticleSource('إيلاف'),
            categoryId: CategoryId::fromInt($categoryId),
            authorIds: array_map(AuthorId::fromInt(...), $authorIds),
            tagIds: array_map(TagId::fromInt(...), $tagIds),
        );
    }

    public static function draft(Clock $clock, ?ArticleContent $content = null, int $id = 1, ArticleOrigin $origin = ArticleOrigin::Staff): Article
    {
        return Article::draft(ArticleId::fromInt($id), $origin, UserId::fromInt(self::WRITER), $content ?? self::content(), $clock);
    }

    /**
     * Scheduled articles are scheduled one day after the clock's now.
     */
    public static function inState(ArticleStatus $status, Clock $clock, ?ArticleContent $content = null, int $id = 1): Article
    {
        $article = self::draft($clock, $content, $id);
        $writer = UserId::fromInt(self::WRITER);
        $editor = UserId::fromInt(self::EDITOR);
        $chief = UserId::fromInt(self::CHIEF);

        $steps = match ($status) {
            ArticleStatus::Draft => [],
            ArticleStatus::InReview => ['submit'],
            ArticleStatus::Returned => ['submit', 'return'],
            ArticleStatus::Approved => ['submit', 'approve'],
            ArticleStatus::Scheduled => ['submit', 'approve', 'schedule'],
            ArticleStatus::Published => ['submit', 'approve', 'publish'],
            ArticleStatus::Unpublished => ['submit', 'approve', 'publish', 'unpublish'],
        };

        foreach ($steps as $step) {
            match ($step) {
                'submit' => $article->submitForReview($writer, $clock),
                'return' => $article->returnToWriter($editor, 'يرجى المراجعة', $clock),
                'approve' => $article->approve($editor, $clock),
                'schedule' => $article->schedule($clock->now()->modify('+1 day'), $chief, $clock),
                'publish' => $article->publish($chief, $clock),
                'unpublish' => $article->unpublish($chief, $clock),
            };
        }

        $article->releaseEvents();

        return $article;
    }
}
