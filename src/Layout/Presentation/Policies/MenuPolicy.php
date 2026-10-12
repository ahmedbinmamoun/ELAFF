<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Policies;

use App\Models\User;
use Elaf\Layout\Application\LayoutPermission;

/**
 * The three menus exist from the start: they are edited, never created or
 * deleted.
 */
final class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(LayoutPermission::ManageMenus->value);
    }

    public function update(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
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
