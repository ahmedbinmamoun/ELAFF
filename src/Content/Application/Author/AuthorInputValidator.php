<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Author;

use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Validation\Fields;
use Elaf\Shared\Application\Validation\InvalidInput;

final readonly class AuthorInputValidator
{
    public function __construct(private AuthorRepository $authors) {}

    public function validate(AuthorInput $input, ?AuthorId $self = null): AuthorData
    {
        $name = Fields::required('name', $input->name, 120);
        $slug = Fields::slug('slug', $input->slug, $name);

        if ($this->authors->slugExists($slug, $self)) {
            throw InvalidInput::slugTaken('slug', $slug->value);
        }

        return new AuthorData(
            name: $name,
            slug: $slug,
            title: Fields::optional('title', $input->title, 120),
            bio: Fields::optional('bio', $input->bio, 2000),
            photoPath: Fields::optional('photo_path', $input->photoPath, 255),
            isColumnist: $input->isColumnist,
            userId: $this->user($input->userId, $self),
            socialLinks: $this->socialLinks($input->socialLinks),
        );
    }

    /**
     * A staff user can be linked to at most one author profile.
     */
    private function user(?int $userId, ?AuthorId $self): ?UserId
    {
        if ($userId === null) {
            return null;
        }

        if ($userId < 1) {
            throw InvalidInput::unknownReference('user_id', $userId);
        }

        $id = UserId::fromInt($userId);
        $linked = $this->authors->findByUser($id);

        if ($linked !== null && ($self === null || ! $linked->id->equals($self))) {
            throw new InvalidInput('user_id', 'This user is already linked to another author.');
        }

        return $id;
    }

    /**
     * @param  array<string, string>  $links
     * @return array<string, string>
     */
    private function socialLinks(array $links): array
    {
        $clean = [];

        foreach ($links as $network => $url) {
            $network = strtolower(trim($network));

            if (preg_match('/^[a-z0-9_-]{1,30}$/', $network) !== 1) {
                throw new InvalidInput('social_links', sprintf('"%s" is not a valid network name.', $network));
            }

            $url = Fields::httpUrl('social_links', $url);

            if ($url !== null) {
                $clean[$network] = $url;
            }
        }

        return $clean;
    }
}
