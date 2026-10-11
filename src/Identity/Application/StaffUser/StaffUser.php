<?php

declare(strict_types=1);

namespace Elaf\Identity\Application\StaffUser;

use Elaf\Identity\Domain\UserId;

final readonly class StaffUser
{
    public function __construct(
        public UserId $id,
        public StaffUserData $data,
    ) {}
}
