<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Application\StaffUser\StaffUserData;
use Elaf\Identity\Application\StaffUser\StaffUserRepository;
use Elaf\Identity\Domain\UserId;
use Elaf\Identity\Infrastructure\EloquentStaffUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\InMemoryStaffUserRepository;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

dataset('staff user repositories', [
    'in-memory' => fn (): array => [
        new InMemoryStaffUserRepository,
        static fn (InMemoryStaffUserRepository $r, string $email): UserId => $r->addNonStaffAccount($email),
    ],
    'eloquent' => fn (): array => [
        app(EloquentStaffUserRepository::class),
        static fn (StaffUserRepository $r, string $email): UserId => UserId::fromInt(
            User::factory()->create(['email' => $email])->assignRole(Role::Contributor->value)->id,
        ),
    ],
]);

it('creates, finds and updates staff users', function (StaffUserRepository $repository, Closure $nonStaff): void {
    $id = $repository->create(new StaffUserData('محرر', 'editor@elaf.test', Role::Editor), 'hash-1');

    expect($repository->find($id)?->data)->toEqual(new StaffUserData('محرر', 'editor@elaf.test', Role::Editor))
        ->and($repository->find(UserId::fromInt(9999)))->toBeNull();

    $repository->update($id, new StaffUserData('رئيس', 'chief@elaf.test', Role::EditorInChief));
    expect($repository->find($id)?->data)->toEqual(new StaffUserData('رئيس', 'chief@elaf.test', Role::EditorInChief));

    $repository->changePassword($id, 'hash-2');
})->with('staff user repositories');

it('does not return accounts without a staff role', function (StaffUserRepository $repository, Closure $nonStaff): void {
    $contributor = $nonStaff($repository, 'guest@elaf.test');

    expect($repository->find($contributor))->toBeNull()
        ->and($repository->emailExists('GUEST@elaf.test'))->toBeTrue();
})->with('staff user repositories');

it('checks email uniqueness, optionally ignoring one user', function (StaffUserRepository $repository, Closure $nonStaff): void {
    $id = $repository->create(new StaffUserData('كاتب', 'writer@elaf.test', Role::Writer), 'hash');

    expect($repository->emailExists('writer@elaf.test'))->toBeTrue()
        ->and($repository->emailExists('writer@elaf.test', $id))->toBeFalse()
        ->and($repository->emailExists('other@elaf.test'))->toBeFalse();
})->with('staff user repositories');
