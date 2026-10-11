<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Tag\EnsureTagsExist;

use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Application\Tag\TagResolver;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;

final readonly class EnsureTagsExistHandler
{
    public function __construct(
        private TagResolver $resolver,
        private Authorizer $authorizer,
    ) {}

    /**
     * @return list<TagId>
     */
    public function handle(EnsureTagsExistCommand $command): array
    {
        if (! $this->authorizer->allows(ContentPermission::CreateArticles->value)) {
            throw NotAuthorized::lacks(ContentPermission::CreateArticles->value);
        }

        return $this->resolver->resolve($command->names);
    }
}
