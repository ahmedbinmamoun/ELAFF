<?php

declare(strict_types=1);

namespace Elaf\Shared\Application\Authorization;

/**
 * Permission check for the current actor, for use cases that must enforce
 * a rule themselves (e.g. "approval requires an Editor or higher").
 */
interface Authorizer
{
    public function allows(string $ability, ?object $subject = null): bool;
}
