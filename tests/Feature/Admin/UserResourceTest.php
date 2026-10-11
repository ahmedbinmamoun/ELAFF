<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Presentation\Filament\Resources\Users\Pages\CreateUser;
use Elaf\Identity\Presentation\Filament\Resources\Users\Pages\EditUser;
use Elaf\Identity\Presentation\Filament\Resources\Users\Pages\ListUsers;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('admin');
    $this->admin = AdminFixtures::user(Role::Admin);
    $this->actingAs($this->admin);
});

it('creates a newsroom user with a role who can then sign in', function (): void {
    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'محرر جديد', 'email' => 'New@Elaf.test', 'role' => 'editor', 'password' => 'a-long-password'])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'new@elaf.test')->sole();
    expect($user->hasRole('editor'))->toBeTrue()
        ->and(Hash::check('a-long-password', $user->password))->toBeTrue();
});

it('changes a role and keeps the password when left blank', function (): void {
    $writer = AdminFixtures::user(Role::Writer);
    $hash = $writer->password;

    Livewire::test(EditUser::class, ['record' => $writer->getRouteKey()])
        ->assertSchemaStateSet(['role' => 'writer'])
        ->fillForm(['role' => 'editor-in-chief', 'password' => ''])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($writer->refresh()->hasRole('editor-in-chief'))->toBeTrue()
        ->and($writer->password)->toBe($hash);
});

it('does not let an admin remove their own admin role', function (): void {
    Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
        ->fillForm(['role' => 'editor'])
        ->call('save')
        ->assertHasFormErrors(['role']);

    expect($this->admin->refresh()->hasRole('admin'))->toBeTrue();
});

it('lists newsroom staff only, without contributors', function (): void {
    $contributor = AdminFixtures::user(Role::Contributor);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$this->admin])
        ->assertCanNotSeeTableRecords([$contributor]);
});

it('rejects a duplicate email and a short password on their fields', function (): void {
    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'x', 'email' => $this->admin->email, 'role' => 'writer', 'password' => 'short'])
        ->call('create')
        ->assertHasFormErrors(['email']);

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'x', 'email' => 'fresh@elaf.test', 'role' => 'writer', 'password' => 'short'])
        ->call('create')
        ->assertHasFormErrors(['password']);
});
