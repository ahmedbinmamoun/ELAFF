<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Author\CreateAuthor;

use Elaf\Content\Application\Author\AuthorInputValidator;
use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;

final readonly class CreateAuthorHandler
{
    public function __construct(
        private AuthorRepository $authors,
        private AuthorInputValidator $validator,
        private Authorizer $authorizer,
    ) {}

    public function handle(CreateAuthorCommand $command): AuthorId
    {
        if (! $this->authorizer->allows(ContentPermission::ManageAuthors->value)) {
            throw NotAuthorized::lacks(ContentPermission::ManageAuthors->value);
        }

        return $this->authors->create($this->validator->validate($command->author));
    }
}
