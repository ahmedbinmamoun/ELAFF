<?php

declare(strict_types=1);

namespace Elaf\Settings\Presentation\Policies;

use App\Models\User;
use Elaf\Settings\Application\SettingsPermission;

final class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(SettingsPermission::ManagePages->value);
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
        return $this->viewAny($user);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
