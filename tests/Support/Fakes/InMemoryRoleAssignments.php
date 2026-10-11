<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Identity\Application\Role;
use Elaf\Identity\Application\RoleAssignments;
use Elaf\Identity\Domain\UserId;

final class InMemoryRoleAssignments implements RoleAssignments
{
    /** @var array<int, array<string, true>> */
    private array $roles = [];

    public function assign(UserId $user, Role $role): void
    {
        $this->roles[$user->value][$role->value] = true;
    }

    public function remove(UserId $user, Role $role): void
    {
        unset($this->roles[$user->value][$role->value]);
    }

    public function has(UserId $user, Role $role): bool
    {
        return isset($this->roles[$user->value][$role->value]);
    }
}
