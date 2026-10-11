<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Category\CreateCategory;

use Elaf\Content\Application\Category\CategoryInputValidator;
use Elaf\Content\Application\Category\CategoryRepository;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;

final readonly class CreateCategoryHandler
{
    public function __construct(
        private CategoryRepository $categories,
        private CategoryInputValidator $validator,
        private Authorizer $authorizer,
    ) {}

    public function handle(CreateCategoryCommand $command): CategoryId
    {
        if (! $this->authorizer->allows(ContentPermission::ManageTaxonomy->value)) {
            throw NotAuthorized::lacks(ContentPermission::ManageTaxonomy->value);
        }

        return $this->categories->create($this->validator->validate($command->category));
    }
}
