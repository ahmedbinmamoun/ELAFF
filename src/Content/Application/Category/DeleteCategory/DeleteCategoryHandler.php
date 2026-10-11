<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Category\DeleteCategory;

use Elaf\Content\Application\Category\CategoryRepository;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Application\Validation\RecordNotFound;

/**
 * Only an empty category can go: articles keep their URLs and need a
 * category, so editors move them first.
 */
final readonly class DeleteCategoryHandler
{
    public function __construct(
        private CategoryRepository $categories,
        private Authorizer $authorizer,
    ) {}

    public function handle(DeleteCategoryCommand $command): void
    {
        if (! $this->authorizer->allows(ContentPermission::ManageTaxonomy->value)) {
            throw NotAuthorized::lacks(ContentPermission::ManageTaxonomy->value);
        }

        $id = CategoryId::fromInt($command->categoryId);

        if ($this->categories->find($id) === null) {
            throw RecordNotFound::of('Category', $command->categoryId);
        }

        if ($this->categories->hasArticles($id)) {
            throw InvalidInput::because('category', 'category_has_articles', 'The category still has articles.');
        }

        if ($this->categories->hasChildren($id)) {
            throw InvalidInput::because('category', 'category_has_children', 'The category still has subcategories.');
        }

        $this->categories->delete($id);
    }
}
