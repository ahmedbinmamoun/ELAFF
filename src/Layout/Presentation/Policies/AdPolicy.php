<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Policies;

use App\Models\User;
use Elaf\Layout\Application\LayoutPermission;

final class AdPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(LayoutPermission::ManageAds->value);
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
