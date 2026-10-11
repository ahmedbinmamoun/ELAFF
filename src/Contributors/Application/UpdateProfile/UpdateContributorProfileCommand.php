<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\UpdateProfile;

/**
 * Always about the signed-in contributor's own profile.
 */
final readonly class UpdateContributorProfileCommand
{
    public function __construct(
        public int $actorId,
        public string $name,
        public string $bio,
        public ?string $phone = null,
        public ?string $photoPath = null,
    ) {}
}
