<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article;

use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\Article;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;

/**
 * Who may act on an article (PRD §2, §2.1). Writers and contributors work on
 * their own articles only, and only until they submit them.
 */
final readonly class ArticleAccess
{
    public function __construct(private Authorizer $authorizer) {}

    public function require(ContentPermission $permission): void
    {
        if (! $this->authorizer->allows($permission->value)) {
            throw NotAuthorized::lacks($permission->value);
        }
    }

    public function requireCanEdit(Article $article, UserId $actor): void
    {
        if ($this->authorizer->allows(ContentPermission::EditAnyArticle->value)) {
            return;
        }

        if (! $article->isCreatedBy($actor)) {
            throw NotAuthorized::because('Only editors can edit articles written by someone else.');
        }

        if (! $article->status()->isEditableByAuthor()) {
            throw NotAuthorized::because(sprintf('The article is locked while "%s".', $article->status()->value));
        }
    }

    /**
     * Submitting or withdrawing: the article's own writer, or an editor.
     */
    public function requireOwnerOrEditor(Article $article, UserId $actor): void
    {
        if (! $article->isCreatedBy($actor) && ! $this->authorizer->allows(ContentPermission::EditAnyArticle->value)) {
            throw NotAuthorized::because('Only the writer or an editor can do this.');
        }
    }
}
