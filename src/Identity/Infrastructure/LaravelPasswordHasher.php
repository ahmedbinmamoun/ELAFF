<?php

declare(strict_types=1);

namespace Elaf\Identity\Infrastructure;

use Elaf\Identity\Application\StaffUser\PasswordHasher;
use Illuminate\Contracts\Hashing\Hasher;

final readonly class LaravelPasswordHasher implements PasswordHasher
{
    public function __construct(private Hasher $hasher) {}

    public function hash(string $plain): string
    {
        return $this->hasher->make($plain);
    }
}
