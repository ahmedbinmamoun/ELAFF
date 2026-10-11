<?php

declare(strict_types=1);

namespace Elaf\Identity\Application\StaffUser\UpdateStaffUser;

use Elaf\Identity\Application\StaffUser\StaffUserInput;

final readonly class UpdateStaffUserCommand
{
    public function __construct(
        public int $actorId,
        public int $userId,
        public StaffUserInput $user,
    ) {}
}
