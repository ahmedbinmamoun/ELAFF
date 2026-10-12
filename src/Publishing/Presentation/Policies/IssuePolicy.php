<?php

declare(strict_types=1);

namespace Elaf\Publishing\Presentation\Policies;

use App\Models\User;
use Elaf\Publishing\Application\IssuePermission;

/**
 * PRD §2: issues are managed by the Editor-in-Chief and Admin.
 */
final class IssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(IssuePermission::ManageIssues->value);
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
