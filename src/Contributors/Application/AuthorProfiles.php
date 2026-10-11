<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application;

use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Contributors\Domain\DisplayName;
use Elaf\Identity\Domain\UserId;

/**
 * The contributor's byline profile in the Content module (FR-CON-04).
 */
interface AuthorProfiles
{
    /**
     * Creates an author linked to $user, with a unique slug from $name.
     */
    public function createFor(UserId $user, DisplayName $name): AuthorId;

    public function rename(AuthorId $author, DisplayName $name): void;
}
