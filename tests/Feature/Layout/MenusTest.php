<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoPagesSeeder;
use Database\Seeders\MenusSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Identity\Application\Role;
use Elaf\Layout\Infrastructure\Persistence\MenuModel;
use Elaf\Layout\Presentation\Filament\Menus\Pages\EditMenu;
use Elaf\Settings\Infrastructure\Persistence\PageModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class, DemoPagesSeeder::class, MenusSeeder::class]);
    Filament::setCurrentPanel('admin');
});

it('seeds the menus from the sections and pages, once', function (): void {
    $this->get('/')->assertOk()
        ->assertSee(route('category.show', 'أخبار-السودان'), false)
        ->assertSee(route('page.show', 'about'), false)
        ->assertSee(route('issues.index'), false);

    $this->seed(MenusSeeder::class);
    expect(MenuModel::query()->count())->toBe(3);
});

it('lets the Editor-in-Chief edit a menu that drives the site (FR-NAV-01)', function (): void {
    $this->actingAs(AdminFixtures::user(Role::EditorInChief));
    $economy = CategoryModel::query()->where('slug', 'الاقتصاد')->value('id');
    $about = PageModel::query()->where('slug', 'about')->value('id');
    $header = MenuModel::query()->where('key', 'header')->firstOrFail();

    Livewire::test(EditMenu::class, ['record' => $header->getRouteKey()])
        ->fillForm(['items' => [
            ['label' => 'اقتصاد وأعمال', 'type' => 'category', 'category_id' => $economy, 'is_visible' => true, 'children' => [
                ['label' => 'عن إيلاف', 'type' => 'page', 'page_id' => $about, 'is_visible' => true],
            ]],
            ['label' => 'رابط مخفي', 'type' => 'url', 'url' => '/hidden', 'is_visible' => false],
            ['label' => 'رسائل القراء', 'type' => 'url', 'url' => '/letters', 'is_visible' => true],
        ]])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->app->forgetScopedInstances();
    $this->get('/')->assertOk()
        ->assertSee('اقتصاد وأعمال')
        ->assertSee('عن إيلاف')
        ->assertSee('رسائل القراء')
        ->assertDontSee('رابط مخفي');
});

it('shows use-case errors on the form', function (): void {
    $this->actingAs(AdminFixtures::user(Role::EditorInChief));
    $footer = MenuModel::query()->where('key', 'footer')->firstOrFail();

    Livewire::test(EditMenu::class, ['record' => $footer->getRouteKey()])
        ->fillForm(['items' => [['label' => 'سيء', 'type' => 'url', 'url' => 'javascript:alert(1)', 'is_visible' => true]]])
        ->call('save')
        ->assertHasFormErrors(['items']);
});

it('drops items whose page is unpublished or deleted', function (): void {
    PageModel::query()->where('slug', 'about')->update(['is_published' => false]);
    PageModel::query()->where('slug', 'contact')->delete();

    $this->get('/')->assertOk()
        ->assertDontSee(route('page.show', 'about'), false)
        ->assertDontSee(route('page.show', 'contact'), false)
        ->assertSee(route('page.show', 'privacy'), false);
});

it('keeps menus for the Editor-in-Chief and Admin (PRD §2)', function (): void {
    $this->actingAs(AdminFixtures::user(Role::Editor))->get('/admin/menus')->assertForbidden();
    $this->actingAs(AdminFixtures::user(Role::EditorInChief))->get('/admin/menus')->assertOk()->assertSee('قائمة التذييل');
});
