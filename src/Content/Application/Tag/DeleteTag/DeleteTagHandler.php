<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Tag\DeleteTag;

use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Application\Tag\TagRepository;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\RecordNotFound;

final readonly class DeleteTagHandler
{
    public function __construct(
        private TagRepository $tags,
        private Authorizer $authorizer,
    ) {}

    public function handle(DeleteTagCommand $command): void
    {
        if (! $this->authorizer->allows(ContentPermission::ManageTaxonomy->value)) {
            throw NotAuthorized::lacks(ContentPermission::ManageTaxonomy->value);
        }

        $id = TagId::fromInt($command->tagId);

        if ($this->tags->find($id) === null) {
            throw RecordNotFound::of('Tag', $command->tagId);
        }

        $this->tags->delete($id);
    }
}
