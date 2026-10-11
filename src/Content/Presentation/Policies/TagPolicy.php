<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Policies;

use App\Models\User;
use Elaf\Content\Application\ContentPermission;

final class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(ContentPermission::ManageTaxonomy->value);
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
        return $this->viewAny($user);
    }

    /**
     * Bulk deletion is off: each delete goes through its use case checks.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }
}
