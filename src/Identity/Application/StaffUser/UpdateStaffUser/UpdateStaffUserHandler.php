<?php

declare(strict_types=1);

namespace Elaf\Identity\Application\StaffUser\UpdateStaffUser;

use Elaf\Identity\Application\IdentityPermission;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Application\StaffUser\PasswordHasher;
use Elaf\Identity\Application\StaffUser\StaffUserInputValidator;
use Elaf\Identity\Application\StaffUser\StaffUserRepository;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Application\Validation\RecordNotFound;

/**
 * An admin cannot take the admin role away from themselves, so the site can
 * never be left without someone able to manage users.
 */
final readonly class UpdateStaffUserHandler
{
    public function __construct(
        private StaffUserRepository $users,
        private StaffUserInputValidator $validator,
        private PasswordHasher $hasher,
        private Authorizer $authorizer,
        private Transaction $transaction,
    ) {}

    public function handle(UpdateStaffUserCommand $command): void
    {
        if (! $this->authorizer->allows(IdentityPermission::ManageUsers->value)) {
            throw NotAuthorized::lacks(IdentityPermission::ManageUsers->value);
        }

        $id = UserId::fromInt($command->userId);
        $current = $this->users->find($id) ?? throw RecordNotFound::of('Staff user', $command->userId);
        $data = $this->validator->validate($command->user, $id);
        $password = $this->validator->password($command->user->password, required: false);

        if ($id->value === $command->actorId && $current->data->role === Role::Admin && $data->role !== Role::Admin) {
            throw InvalidInput::because('role', 'cannot_demote_self', 'You cannot remove your own admin role.');
        }

        $this->transaction->run(function () use ($id, $data, $password): void {
            $this->users->update($id, $data);

            if ($password !== null) {
                $this->users->changePassword($id, $this->hasher->hash($password));
            }
        });
    }
}
