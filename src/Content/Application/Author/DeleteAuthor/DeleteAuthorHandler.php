<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Author\DeleteAuthor;

use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Application\Validation\RecordNotFound;

/**
 * An author who signs articles stays: removing them would silently change
 * published bylines.
 */
final readonly class DeleteAuthorHandler
{
    public function __construct(
        private AuthorRepository $authors,
        private Authorizer $authorizer,
    ) {}

    public function handle(DeleteAuthorCommand $command): void
    {
        if (! $this->authorizer->allows(ContentPermission::ManageAuthors->value)) {
            throw NotAuthorized::lacks(ContentPermission::ManageAuthors->value);
        }

        $id = AuthorId::fromInt($command->authorId);

        if ($this->authors->find($id) === null) {
            throw RecordNotFound::of('Author', $command->authorId);
        }

        if ($this->authors->hasArticles($id)) {
            throw InvalidInput::because('author', 'author_has_articles', 'The author still signs articles.');
        }

        $this->authors->delete($id);
    }
}
