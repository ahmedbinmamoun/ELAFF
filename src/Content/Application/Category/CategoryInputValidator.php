<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Category;

use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Shared\Application\Validation\Fields;
use Elaf\Shared\Application\Validation\InvalidInput;

final readonly class CategoryInputValidator
{
    public function __construct(private CategoryRepository $categories) {}

    /**
     * @param  CategoryId|null  $self  the category being updated, null on create
     */
    public function validate(CategoryInput $input, ?CategoryId $self = null): CategoryData
    {
        $name = Fields::required('name', $input->name, 120);
        $slug = Fields::slug('slug', $input->slug, $name);

        if ($this->categories->slugExists($slug, $self)) {
            throw InvalidInput::slugTaken('slug', $slug->value);
        }

        return new CategoryData(
            name: $name,
            slug: $slug,
            description: Fields::optional('description', $input->description, 1000),
            parentId: $this->parent($input->parentId, $self),
            sortOrder: $input->sortOrder,
            isVisible: $input->isVisible,
            isOpenToContributors: $input->isOpenToContributors,
            seoTitle: Fields::optional('seo_title', $input->seoTitle, 255),
            seoDescription: Fields::optional('seo_description', $input->seoDescription, 500),
        );
    }

    /**
     * The parent must exist and must not be the category itself or one of
     * its descendants (no cycles).
     */
    private function parent(?int $parentId, ?CategoryId $self): ?CategoryId
    {
        if ($parentId === null) {
            return null;
        }

        $ancestor = $parentId > 0 ? $this->categories->find(CategoryId::fromInt($parentId)) : null;

        if ($ancestor === null) {
            throw InvalidInput::unknownReference('parent_id', $parentId);
        }

        $parent = $ancestor->id;

        while ($self !== null && $ancestor !== null) {
            if ($ancestor->id->equals($self)) {
                throw InvalidInput::because('parent_id', 'category_cycle', 'A category cannot be nested under itself or its own subcategory.');
            }

            $ancestor = $ancestor->data->parentId === null ? null : $this->categories->find($ancestor->data->parentId);
        }

        return $parent;
    }
}
