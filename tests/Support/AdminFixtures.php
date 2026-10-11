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
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Article\Title;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Text\Slug;
use Elaf\Shared\Domain\Time\Clock;

/**
 * Real users and articles for admin feature tests. Articles reach their
 * state through the domain (submit, approve, …), never by writing columns.
 */
final class AdminFixtures
{
    public static function user(Role $role, string $name = ''): User
    {
        $user = User::factory()->create($name === '' ? [] : ['name' => $name]);
        $user->assignRole($role->value);

        return $user;
    }

    public static function category(string $name = 'أخبار'): CategoryModel
    {
        return CategoryModel::query()->firstOrCreate(['slug' => Slug::fromText($name)->value], ['name' => $name]);
    }

    public static function author(string $name = 'كاتب الاختبار'): AuthorModel
    {
        return AuthorModel::query()->firstOrCreate(['slug' => Slug::fromText($name)->value], ['name' => $name]);
    }

    /**
     * @param  User  $writer  creator of the article
     * @param  User|null  $reviewer  approves/returns (must differ from the writer)
     * @param  User|null  $publisher  publishes/schedules/unpublishes
     */
    public static function article(
        ArticleStatus $status,
        User $writer,
        ?User $reviewer = null,
        ?User $publisher = null,
        string $title = 'مقال للاختبار',
    ): ArticleModel {
        /** @var ArticleRepository $repository */
        $repository = app(ArticleRepository::class);
        /** @var Clock $clock */
        $clock = app(Clock::class);
        $w = UserId::fromInt($writer->id);
        $r = UserId::fromInt(($reviewer ?? self::user(Role::Editor))->id);
        $p = UserId::fromInt(($publisher ?? self::user(Role::EditorInChief))->id);

        $article = Article::draft($repository->nextId(), ArticleOrigin::Staff, $w, new ArticleContent(
            title: new Title($title),
            slug: Slug::fromText($title),
            body: ArticleBody::fromSanitizedHtml('<p>نص تجريبي</p>'),
            source: new ArticleSource('إيلاف'),
            categoryId: CategoryId::fromInt(self::category()->id),
            authorIds: [AuthorId::fromInt(self::author()->id)],
        ), $clock);

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
                'submit' => $article->submitForReview($w, $clock),
                'return' => $article->returnToWriter($r, 'راجع الأرقام', $clock),
                'approve' => $article->approve($r, $clock),
                'schedule' => $article->schedule($clock->now()->modify('+1 day'), $p, $clock),
                'publish' => $article->publish($p, $clock),
                'unpublish' => $article->unpublish($p, $clock),
            };
        }

        $article->releaseEvents();
        $repository->save($article);

        return ArticleModel::query()->findOrFail($article->id()->value);
    }
}
