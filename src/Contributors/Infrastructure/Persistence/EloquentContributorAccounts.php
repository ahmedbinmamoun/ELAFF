<?php

declare(strict_types=1);

namespace Elaf\Contributors\Infrastructure\Persistence;

use App\Models\User;
use Elaf\Contributors\Application\ContributorAccounts;
use Elaf\Contributors\Domain\ContributorStatus;
use Elaf\Contributors\Domain\DisplayName;
use Elaf\Identity\Domain\UserId;

final class EloquentContributorAccounts implements ContributorAccounts
{
    public function create(DisplayName $name, string $email, string $passwordHash): UserId
    {
        $user = new User;
        $user->forceFill([
            'name' => $name->value,
            'email' => mb_strtolower($email),
            'password' => $passwordHash,
            'type' => 'contributor',
            'status' => ContributorStatus::PendingEmail->value,
        ])->save();

        return UserId::fromInt($user->id);
    }

    public function emailExists(string $email): bool
    {
        return User::query()->where('email', mb_strtolower($email))->exists();
    }
}
