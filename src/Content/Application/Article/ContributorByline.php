<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article;

use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Authorization\NotAuthorized;

/**
 * A contributor always signs with their own author profile (FR-CON-04); they
 * cannot put someone else's name on their article. Editors keep choosing
 * bylines freely.
 */
final readonly class ContributorByline
{
    public function __construct(private AuthorRepository $authors) {}

    public function apply(ArticleInput $input, ArticleOrigin $origin, UserId $writer, UserId $actor): ArticleInput
    {
        if ($origin !== ArticleOrigin::Contributor || ! $actor->equals($writer)) {
            return $input;
        }

        $author = $this->authors->findByUser($writer)
            ?? throw NotAuthorized::because('The contributor has no author profile yet.');

        return $input->withAuthorIds([$author->id->value]);
    }
}
