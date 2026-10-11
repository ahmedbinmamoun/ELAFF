<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article;

use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;

/**
 * Who may act on an article (PRD §2, §2.1). Writers and contributors work on
 * their own articles only, and only until they submit them.
 *
 * The can*() questions take plain values so read-side callers (admin
 * policies) ask the same rule without loading the aggregate.
 */
final readonly class ArticleAccess
{
    public function __construct(
        private Authorizer $authorizer,
        private EditorialSettings $settings,
    ) {}

    public function require(ContentPermission $permission): void
    {
        if (! $this->authorizer->allows($permission->value)) {
            throw NotAuthorized::lacks($permission->value);
        }
    }

    public function canEdit(UserId $actor, UserId $writer, ArticleStatus $status): bool
    {
        return $this->authorizer->allows(ContentPermission::EditAnyArticle->value)
            || ($actor->equals($writer) && $status->isEditableByAuthor());
    }

    /**
     * Submitting or withdrawing: the article's own writer, or an editor.
     */
    public function canSubmit(UserId $actor, UserId $writer): bool
    {
        return $actor->equals($writer) || $this->authorizer->allows(ContentPermission::EditAnyArticle->value);
    }

    /**
     * Publishing is the Editor-in-Chief's (PRD §2), unless the setting lets
     * editors publish contributor articles (FR-CON-08).
     */
    public function canPublish(ArticleOrigin $origin): bool
    {
        return $this->authorizer->allows(ContentPermission::PublishArticles->value)
            || ($origin === ArticleOrigin::Contributor
                && $this->settings->editorsCanPublishContributorArticles()
                && $this->authorizer->allows(ContentPermission::ReviewArticles->value));
    }

    public function requireCanPublish(Article $article): void
    {
        if (! $this->canPublish($article->origin())) {
            throw NotAuthorized::lacks(ContentPermission::PublishArticles->value);
        }
    }

    public function requireCanEdit(Article $article, UserId $actor): void
    {
        if ($this->canEdit($actor, $article->createdBy(), $article->status())) {
            return;
        }

        throw $article->isCreatedBy($actor)
            ? NotAuthorized::because(sprintf('The article is locked while "%s".', $article->status()->value))
            : NotAuthorized::because('Only editors can edit articles written by someone else.');
    }

    public function requireOwnerOrEditor(Article $article, UserId $actor): void
    {
        if (! $this->canSubmit($actor, $article->createdBy())) {
            throw NotAuthorized::because('Only the writer or an editor can do this.');
        }
    }
}
