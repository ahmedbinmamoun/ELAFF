<?php

declare(strict_types=1);

namespace Elaf\Identity\Application\StaffUser\CreateStaffUser;

use Elaf\Identity\Application\IdentityPermission;
use Elaf\Identity\Application\StaffUser\PasswordHasher;
use Elaf\Identity\Application\StaffUser\StaffUserInputValidator;
use Elaf\Identity\Application\StaffUser\StaffUserRepository;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;

final readonly class CreateStaffUserHandler
{
    public function __construct(
        private StaffUserRepository $users,
        private StaffUserInputValidator $validator,
        private PasswordHasher $hasher,
        private Authorizer $authorizer,
    ) {}

    public function handle(CreateStaffUserCommand $command): UserId
    {
        if (! $this->authorizer->allows(IdentityPermission::ManageUsers->value)) {
            throw NotAuthorized::lacks(IdentityPermission::ManageUsers->value);
        }

        $data = $this->validator->validate($command->user);
        $password = $this->validator->password($command->user->password, required: true) ?? '';

        return $this->users->create($data, $this->hasher->hash($password));
    }
}
