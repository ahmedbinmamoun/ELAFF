<?php

declare(strict_types=1);

use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Identity\Application\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdminFixtures;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('sends guests to the login page', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->get('/admin/login')->assertOk()->assertSee('dir="rtl"', false);
});

it('lets every newsroom role in', function (Role $role): void {
    $this->actingAs(AdminFixtures::user($role))->get('/admin')->assertOk();
})->with(Role::staff());

it('keeps contributors and users without a role out (PRD §16)', function (?Role $role): void {
    $user = $role === null ? App\Models\User::factory()->create() : AdminFixtures::user($role);

    $this->actingAs($user)->get('/admin')->assertForbidden();
})->with([Role::Contributor, null]);

it('shows each role only the sections it may manage', function (Role $role, array $visible, array $hidden): void {
    $this->actingAs(AdminFixtures::user($role));

    foreach ($visible as $path) {
        $this->get("/admin/{$path}")->assertOk();
    }

    foreach ($hidden as $path) {
        $this->get("/admin/{$path}")->assertForbidden();
    }
})->with([
    'writer' => [Role::Writer, ['articles', 'articles/create'], ['categories', 'tags', 'series', 'authors', 'users']],
    'editor' => [Role::Editor, ['articles', 'categories', 'tags', 'series', 'authors'], ['users']],
    'editor-in-chief' => [Role::EditorInChief, ['articles', 'categories', 'tags', 'series', 'authors'], ['users']],
    'admin' => [Role::Admin, ['articles', 'categories', 'tags', 'series', 'authors', 'users', 'users/create'], []],
]);
