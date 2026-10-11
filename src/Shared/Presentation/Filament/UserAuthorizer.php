<?php

declare(strict_types=1);

namespace Elaf\Shared\Presentation\Filament;

use Elaf\Shared\Application\Authorization\Authorizer;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The Authorizer port answered for a given user, so policies can ask
 * Application access rules about the user Laravel passes them.
 */
final readonly class UserAuthorizer implements Authorizer
{
    public function __construct(
        private Gate $gate,
        private Authenticatable $user,
    ) {}

    public function allows(string $ability, ?object $subject = null): bool
    {
        return $this->gate->forUser($this->user)->allows($ability, $subject === null ? [] : [$subject]);
    }
}
