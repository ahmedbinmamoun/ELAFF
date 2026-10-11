<?php

declare(strict_types=1);

namespace Elaf\Identity\Infrastructure;

use App\Models\User;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Application\RoleAssignments;
use Elaf\Identity\Domain\UserId;

final class SpatieRoleAssignments implements RoleAssignments
{
    public function assign(UserId $user, Role $role): void
    {
        User::query()->findOrFail($user->value)->assignRole($role->value);
    }

    public function remove(UserId $user, Role $role): void
    {
        $model = User::query()->findOrFail($user->value);

        if ($model->hasRole($role->value)) {
            $model->removeRole($role->value);
        }
    }
}
