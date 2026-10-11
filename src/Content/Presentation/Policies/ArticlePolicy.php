<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Policies;

use App\Models\User;
use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\EditorialSettings;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Presentation\Filament\UserAuthorizer;
use Illuminate\Contracts\Auth\Access\Gate;

/**
 * Admin-panel abilities. Editing defers to ArticleAccess, the same rule the
 * use cases enforce. Articles are never deleted (they are unpublished).
 */
final readonly class ArticlePolicy
{
    public function __construct(
        private Gate $gate,
        private EditorialSettings $settings,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can(ContentPermission::CreateArticles->value);
    }

    public function view(User $user, ArticleModel $article): bool
    {
        return $article->created_by === $user->id || $user->can(ContentPermission::EditAnyArticle->value);
    }

    public function create(User $user): bool
    {
        return $user->can(ContentPermission::CreateArticles->value);
    }

    public function update(User $user, ArticleModel $article): bool
    {
        return $this->access($user)->canEdit(
            UserId::fromInt($user->id),
            UserId::fromInt($article->created_by),
            ArticleStatus::from($article->status),
        );
    }

    public function submit(User $user, ArticleModel $article): bool
    {
        return $this->access($user)->canSubmit(UserId::fromInt($user->id), UserId::fromInt($article->created_by));
    }

    /**
     * Publish or schedule (FR-CON-08 lets editors publish contributor articles).
     */
    public function publish(User $user, ArticleModel $article): bool
    {
        return $this->access($user)->canPublish(ArticleOrigin::from($article->origin));
    }

    public function delete(User $user, ArticleModel $article): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function access(User $user): ArticleAccess
    {
        return new ArticleAccess(new UserAuthorizer($this->gate, $user), $this->settings);
    }
}
