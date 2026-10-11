<?php

declare(strict_types=1);

namespace Elaf\Identity\Application;

use Elaf\Identity\Domain\UserId;

interface RoleAssignments
{
    public function assign(UserId $user, Role $role): void;

    public function remove(UserId $user, Role $role): void;
}
