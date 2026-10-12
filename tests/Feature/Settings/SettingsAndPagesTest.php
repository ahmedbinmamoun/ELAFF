<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Identity\Application\Role;
use Elaf\Settings\Infrastructure\Persistence\PageModel;
use Elaf\Settings\Presentation\Filament\ManageSettings;
use Elaf\Settings\Presentation\Filament\Pages\Pages\CreatePage;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    Filament::setCurrentPanel('admin');
});

it('opens site settings to the Admin only (PRD §2)', function (Role $role, int $status): void {
    $this->actingAs(AdminFixtures::user($role))->get('/admin/manage-settings')->assertStatus($status);
})->with([
    'editor-in-chief' => [Role::EditorInChief, 403],
    'admin' => [Role::Admin, 200],
]);

it('saves settings and the public site shows them at once (FR-SET-01, 02)', function (): void {
    $this->get('/')->assertOk();
    $this->actingAs(AdminFixtures::user(Role::Admin));

    Livewire::test(ManageSettings::class)
        ->fillForm([
            'name' => 'إيلاف الجديدة',
            'motto' => 'شعار تجريبي',
            'masthead_editor_in_chief' => 'اسم تجريبي',
            'phone' => '+249 912 000 000',
            'whatsapp_url' => 'https://whatsapp.com/channel/test',
            'analytics_id' => 'G-TEST12345',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // A new request: per-request objects start over, the cache does not.
    $this->app->forgetScopedInstances();
    $this->get('/')->assertOk()
        ->assertSee('إيلاف الجديدة')
        ->assertSee('شعار تجريبي')
        ->assertSee('اسم تجريبي')
        ->assertSee('+249 912 000 000')
        ->assertSee('https://whatsapp.com/channel/test', false)
        ->assertSee('googletagmanager.com/gtag/js?id=G-TEST12345', false);
});

it('shows form errors from the use case', function (): void {
    $this->actingAs(AdminFixtures::user(Role::Admin));

    Livewire::test(ManageSettings::class)
        ->fillForm(['analytics_id' => 'nope'])
        ->call('save')
        ->assertHasFormErrors(['analytics_id']);
});

it('publishes static pages at /page/{slug} (FR-PAG-01)', function (): void {
    $this->actingAs(AdminFixtures::user(Role::EditorInChief));

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'من نحن', 'slug' => 'about', 'body' => '<p>صحيفة تجريبية</p>', 'is_published' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->get('/page/about')->assertOk()->assertSee('من نحن')->assertSee('صحيفة تجريبية');

    PageModel::query()->where('slug', 'about')->update(['is_published' => false]);
    $this->get('/page/about')->assertNotFound();
    $this->get('/page/missing')->assertNotFound();
});

it('keeps pages for the Editor-in-Chief and Admin', function (): void {
    $this->actingAs(AdminFixtures::user(Role::Editor))->get('/admin/pages')->assertForbidden();
    $this->actingAs(AdminFixtures::user(Role::EditorInChief))->get('/admin/pages')->assertOk();
});
