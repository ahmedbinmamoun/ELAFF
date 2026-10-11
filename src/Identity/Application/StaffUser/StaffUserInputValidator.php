<?php

declare(strict_types=1);

namespace Elaf\Identity\Application\StaffUser;

use Elaf\Identity\Application\Role;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Validation\Fields;
use Elaf\Shared\Application\Validation\InvalidInput;

final readonly class StaffUserInputValidator
{
    public const MIN_PASSWORD_LENGTH = 10;

    public function __construct(private StaffUserRepository $users) {}

    public function validate(StaffUserInput $input, ?UserId $self = null): StaffUserData
    {
        $email = mb_strtolower(Fields::required('email', $input->email, 255));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidInput::because('email', 'invalid_email', 'The email address is not valid.');
        }

        if ($this->users->emailExists($email, $self)) {
            throw InvalidInput::because('email', 'email_taken', 'The email address is already in use.');
        }

        $role = Role::tryFrom($input->role);

        if ($role === null || ! $role->isStaff()) {
            throw InvalidInput::because('role', 'invalid_role', sprintf('"%s" is not a newsroom role.', $input->role));
        }

        return new StaffUserData(Fields::required('name', $input->name, 120), $email, $role);
    }

    public function password(?string $password, bool $required): ?string
    {
        if ($password === null || $password === '') {
            if ($required) {
                throw InvalidInput::required('password');
            }

            return null;
        }

        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw InvalidInput::tooShort('password', self::MIN_PASSWORD_LENGTH);
        }

        return $password;
    }
}
