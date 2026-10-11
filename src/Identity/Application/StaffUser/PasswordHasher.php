<?php

declare(strict_types=1);

namespace Elaf\Identity\Application\StaffUser;

interface PasswordHasher
{
    public function hash(string $plain): string;
}
