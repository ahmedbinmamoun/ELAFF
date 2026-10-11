<?php

declare(strict_types=1);

namespace Elaf\Identity\Application\StaffUser\CreateStaffUser;

use Elaf\Identity\Application\StaffUser\StaffUserInput;

final readonly class CreateStaffUserCommand
{
    public function __construct(public StaffUserInput $user) {}
}
