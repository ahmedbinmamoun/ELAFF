<?php

declare(strict_types=1);

namespace Elaf\Identity\Infrastructure;

use App\Models\User;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Application\StaffUser\StaffUser;
use Elaf\Identity\Application\StaffUser\StaffUserData;
use Elaf\Identity\Application\StaffUser\StaffUserRepository;
use Elaf\Identity\Domain\UserId;
use Illuminate\Database\ConnectionInterface;

final readonly class EloquentStaffUserRepository implements StaffUserRepository
{
    public function __construct(private ConnectionInterface $db) {}

    public function create(StaffUserData $data, string $passwordHash): UserId
    {
        return $this->db->transaction(static function () use ($data, $passwordHash): UserId {
            $user = new User;
            $user->forceFill(['name' => $data->name, 'email' => $data->email, 'password' => $passwordHash])->save();
            $user->syncRoles([$data->role->value]);

            return UserId::fromInt($user->id);
        });
    }

    public function update(UserId $id, StaffUserData $data): void
    {
        $this->db->transaction(static function () use ($id, $data): void {
            $user = User::query()->findOrFail($id->value);
            $user->forceFill(['name' => $data->name, 'email' => $data->email])->save();
            $user->syncRoles([$data->role->value]);
        });
    }

    public function changePassword(UserId $id, string $passwordHash): void
    {
        User::query()->whereKey($id->value)->update(['password' => $passwordHash]);
    }

    public function find(UserId $id): ?StaffUser
    {
        $user = User::query()->with('roles')->find($id->value);
        $role = $user === null ? null : $this->staffRole($user);

        return $user === null || $role === null
            ? null
            : new StaffUser(UserId::fromInt($user->id), new StaffUserData($user->name, $user->email, $role));
    }

    public function emailExists(string $email, ?UserId $except = null): bool
    {
        return User::query()
            ->where('email', mb_strtolower($email))
            ->when($except !== null, static fn ($q) => $q->whereKeyNot($except?->value))
            ->exists();
    }

    private function staffRole(User $user): ?Role
    {
        foreach ($user->getRoleNames() as $name) {
            $role = is_string($name) ? Role::tryFrom($name) : null;

            if ($role?->isStaff() === true) {
                return $role;
            }
        }

        return null;
    }
}
