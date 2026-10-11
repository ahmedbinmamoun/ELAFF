<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Tag\RenameTag;

use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Application\Tag\TagRepository;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\Fields;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Application\Validation\RecordNotFound;

/**
 * The slug follows the name, so tag URLs match what readers see.
 */
final readonly class RenameTagHandler
{
    public function __construct(
        private TagRepository $tags,
        private Authorizer $authorizer,
    ) {}

    public function handle(RenameTagCommand $command): void
    {
        if (! $this->authorizer->allows(ContentPermission::ManageTaxonomy->value)) {
            throw NotAuthorized::lacks(ContentPermission::ManageTaxonomy->value);
        }

        $id = TagId::fromInt($command->tagId);

        if ($this->tags->find($id) === null) {
            throw RecordNotFound::of('Tag', $command->tagId);
        }

        $name = Fields::required('name', $command->name, 60);
        $slug = Fields::slug('name', null, $name);
        $existing = $this->tags->findBySlug($slug);

        if ($existing !== null && ! $existing->id->equals($id)) {
            throw InvalidInput::slugTaken('name', $slug->value);
        }

        $this->tags->rename($id, $name, $slug);
    }
}
