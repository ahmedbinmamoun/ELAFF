<?php

declare(strict_types=1);

namespace Elaf\Identity\Presentation\Policies;

use App\Models\User;
use Elaf\Identity\Application\IdentityPermission;

/**
 * Users and roles: admin only (PRD §2). Accounts are never deleted here.
 */
final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(IdentityPermission::ManageUsers->value);
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
