<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application;

use Elaf\Contributors\Domain\DisplayName;
use Elaf\Identity\Domain\UserId;

/**
 * The sign-in account behind a contributor (the users table).
 */
interface ContributorAccounts
{
    public function create(DisplayName $name, string $email, string $passwordHash): UserId;

    /**
     * Across all accounts, staff included.
     */
    public function emailExists(string $email): bool;
}
