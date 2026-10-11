<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Category\UpdateCategory;

use Elaf\Content\Application\Category\CategoryInputValidator;
use Elaf\Content\Application\Category\CategoryRepository;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\RecordNotFound;

final readonly class UpdateCategoryHandler
{
    public function __construct(
        private CategoryRepository $categories,
        private CategoryInputValidator $validator,
        private Authorizer $authorizer,
    ) {}

    public function handle(UpdateCategoryCommand $command): void
    {
        if (! $this->authorizer->allows(ContentPermission::ManageTaxonomy->value)) {
            throw NotAuthorized::lacks(ContentPermission::ManageTaxonomy->value);
        }

        $id = CategoryId::fromInt($command->categoryId);

        if ($this->categories->find($id) === null) {
            throw RecordNotFound::of('Category', $command->categoryId);
        }

        $this->categories->update($id, $this->validator->validate($command->category, $id));
    }
}
