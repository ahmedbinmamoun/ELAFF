<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Policies;

use App\Models\User;
use Elaf\Contributors\Application\ContributorPermission;

/**
 * Contributors are moderated through actions; never created, edited as raw
 * records or deleted from the admin.
 */
final class ContributorProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(ContributorPermission::ManageContributors->value);
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user): bool
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
