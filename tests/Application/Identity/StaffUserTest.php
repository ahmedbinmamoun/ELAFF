<?php

declare(strict_types=1);

use Elaf\Identity\Application\IdentityPermission;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Application\StaffUser\CreateStaffUser\CreateStaffUserCommand;
use Elaf\Identity\Application\StaffUser\CreateStaffUser\CreateStaffUserHandler;
use Elaf\Identity\Application\StaffUser\StaffUserInput;
use Elaf\Identity\Application\StaffUser\UpdateStaffUser\UpdateStaffUserCommand;
use Elaf\Identity\Application\StaffUser\UpdateStaffUser\UpdateStaffUserHandler;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Application\Validation\RecordNotFound;
use Tests\Support\ContentKit;

beforeEach(function (): void {
    $this->kit = new ContentKit;
    $this->kit->authorizer->grant(IdentityPermission::ManageUsers->value);
    $this->create = fn (StaffUserInput $input) => $this->kit->make(CreateStaffUserHandler::class)->handle(new CreateStaffUserCommand($input));
    $this->update = fn (int $actor, int $user, StaffUserInput $input) => $this->kit->make(UpdateStaffUserHandler::class)->handle(new UpdateStaffUserCommand($actor, $user, $input));
    $this->reason = static function (callable $work): ?string {
        try {
            $work();
        } catch (InvalidInput $e) {
            return $e->field.':'.$e->reason;
        }

        return null;
    };
});

it('creates a staff user with a hashed password and a newsroom role', function (): void {
    $id = ($this->create)(new StaffUserInput(' محرر ', 'Editor@Elaf.TEST', 'editor', 'a-long-password'));
    $user = $this->kit->users->find($id);

    expect($user?->data->name)->toBe('محرر')
        ->and($user?->data->email)->toBe('editor@elaf.test')
        ->and($user?->data->role)->toBe(Role::Editor)
        ->and($this->kit->users->passwordHashes[$id->value])->toBe('hashed:a-long-password');
});

it('validates staff user input', function (StaffUserInput $input, string $expected): void {
    $this->kit->users->addNonStaffAccount('taken@elaf.test');

    expect(($this->reason)(fn () => ($this->create)($input)))->toBe($expected);
})->with([
    'bad email' => [new StaffUserInput('x', 'not-an-email', 'writer', 'a-long-password'), 'email:invalid_email'],
    'email taken (also by contributors)' => [new StaffUserInput('x', 'TAKEN@elaf.test', 'writer', 'a-long-password'), 'email:email_taken'],
    'contributor is not a staff role' => [new StaffUserInput('x', 'a@elaf.test', 'contributor', 'a-long-password'), 'role:invalid_role'],
    'unknown role' => [new StaffUserInput('x', 'a@elaf.test', 'boss', 'a-long-password'), 'role:invalid_role'],
    'short password' => [new StaffUserInput('x', 'a@elaf.test', 'writer', 'short'), 'password:too_short'],
    'missing password' => [new StaffUserInput('x', 'a@elaf.test', 'writer'), 'password:required'],
]);

it('updates name, email and role, keeping the password unless a new one is given', function (): void {
    $id = ($this->create)(new StaffUserInput('كاتب', 'w@elaf.test', 'writer', 'a-long-password'));

    ($this->update)(99, $id->value, new StaffUserInput('محرر', 'e@elaf.test', 'editor'));
    expect($this->kit->users->find($id)?->data->role)->toBe(Role::Editor)
        ->and($this->kit->users->passwordHashes[$id->value])->toBe('hashed:a-long-password');

    ($this->update)(99, $id->value, new StaffUserInput('محرر', 'e@elaf.test', 'editor', 'another-password'));
    expect($this->kit->users->passwordHashes[$id->value])->toBe('hashed:another-password');
});

it('stops an admin from removing their own admin role', function (): void {
    $admin = ($this->create)(new StaffUserInput('مدير', 'admin@elaf.test', 'admin', 'a-long-password'));

    expect(($this->reason)(fn () => ($this->update)($admin->value, $admin->value, new StaffUserInput('مدير', 'admin@elaf.test', 'editor'))))
        ->toBe('role:cannot_demote_self');

    $other = ($this->create)(new StaffUserInput('مدير ٢', 'admin2@elaf.test', 'admin', 'a-long-password'));
    ($this->update)($admin->value, $other->value, new StaffUserInput('مدير ٢', 'admin2@elaf.test', 'editor'));
    expect($this->kit->users->find($other)?->data->role)->toBe(Role::Editor);
});

it('requires the users permission and an existing staff user', function (): void {
    expect(fn () => ($this->update)(1, 42, new StaffUserInput('x', 'x@elaf.test', 'writer')))->toThrow(RecordNotFound::class);

    $this->kit->authorizer->revoke(IdentityPermission::ManageUsers->value);
    expect(fn () => ($this->create)(new StaffUserInput('x', 'x@elaf.test', 'writer', 'a-long-password')))->toThrow(NotAuthorized::class);
});
