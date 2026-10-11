<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Identity\Application\StaffUser\PasswordHasher;

final class FakePasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return 'hashed:'.$plain;
    }
}
