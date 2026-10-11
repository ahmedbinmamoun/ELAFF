<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Author\UpdateAuthor;

use Elaf\Content\Application\Author\AuthorInputValidator;
use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\RecordNotFound;

final readonly class UpdateAuthorHandler
{
    public function __construct(
        private AuthorRepository $authors,
        private AuthorInputValidator $validator,
        private Authorizer $authorizer,
    ) {}

    public function handle(UpdateAuthorCommand $command): void
    {
        if (! $this->authorizer->allows(ContentPermission::ManageAuthors->value)) {
            throw NotAuthorized::lacks(ContentPermission::ManageAuthors->value);
        }

        $id = AuthorId::fromInt($command->authorId);

        if ($this->authors->find($id) === null) {
            throw RecordNotFound::of('Author', $command->authorId);
        }

        $this->authors->update($id, $this->validator->validate($command->author, $id));
    }
}
