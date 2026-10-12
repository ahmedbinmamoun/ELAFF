<?php

declare(strict_types=1);

namespace Elaf\Engagement\Presentation\Policies;

use App\Models\User;
use Elaf\Engagement\Application\EngagementPermission;

/**
 * Letters arrive from the public form; editors moderate, nobody edits or
 * deletes them in the admin.
 */
final class ReaderLetterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(EngagementPermission::ModerateLetters->value);
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
