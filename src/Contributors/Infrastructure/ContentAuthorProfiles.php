<?php

declare(strict_types=1);

namespace Elaf\Contributors\Infrastructure;

use Elaf\Content\Application\Author\AuthorData;
use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Contributors\Application\AuthorProfiles;
use Elaf\Contributors\Domain\DisplayName;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Text\Slug;
use RuntimeException;

/**
 * Author profiles for contributors, kept in the Content module's authors.
 */
final readonly class ContentAuthorProfiles implements AuthorProfiles
{
    public function __construct(private AuthorRepository $authors) {}

    public function createFor(UserId $user, DisplayName $name): AuthorId
    {
        return $this->authors->create(new AuthorData(
            name: $name->value,
            slug: $this->uniqueSlug(Slug::fromText($name->value)),
            userId: $user,
        ));
    }

    public function rename(AuthorId $author, DisplayName $name): void
    {
        $current = $this->authors->find($author) ?? throw new RuntimeException("Author {$author} does not exist.");
        $data = $current->data;

        $this->authors->update($author, new AuthorData(
            name: $name->value,
            slug: $data->slug,
            title: $data->title,
            bio: $data->bio,
            photoPath: $data->photoPath,
            isColumnist: $data->isColumnist,
            userId: $data->userId,
            socialLinks: $data->socialLinks,
        ));
    }

    private function uniqueSlug(Slug $base): Slug
    {
        $slug = $base;

        for ($n = 2; $this->authors->slugExists($slug); $n++) {
            $slug = Slug::fromText(mb_substr($base->value, 0, Slug::MAX_LENGTH - 6).'-'.$n);
        }

        return $slug;
    }
}
