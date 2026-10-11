<?php

declare(strict_types=1);

namespace Elaf\Identity\Application\StaffUser;

use Elaf\Identity\Domain\UserId;

interface StaffUserRepository
{
    public function create(StaffUserData $data, string $passwordHash): UserId;

    public function update(UserId $id, StaffUserData $data): void;

    public function changePassword(UserId $id, string $passwordHash): void;

    /**
     * Null when the user does not exist or holds no staff role.
     */
    public function find(UserId $id): ?StaffUser;

    /**
     * Across all accounts, staff and contributors (emails are unique).
     */
    public function emailExists(string $email, ?UserId $except = null): bool;
}
