<?php

declare(strict_types=1);

namespace Elaf\Identity\Application\StaffUser;

use Elaf\Identity\Application\Role;

/**
 * A newsroom account (lightweight model, PRD §6.5).
 */
final readonly class StaffUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public Role $role,
    ) {}
}
