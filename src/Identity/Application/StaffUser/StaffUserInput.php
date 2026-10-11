<?php

declare(strict_types=1);

namespace Elaf\Identity\Application\StaffUser;

/**
 * Raw form values. A null password keeps the current one on update.
 */
final readonly class StaffUserInput
{
    public function __construct(
        public string $name,
        public string $email,
        public string $role,
        public ?string $password = null,
    ) {}
}
