<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\EditArticle;
use Elaf\Content\Presentation\Filament\Resources\Categories\Pages\EditCategory;
use Elaf\Identity\Application\Role;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    Filament::setCurrentPanel('admin');
});

it('lets editors pick a section icon, shown in the public navigation', function (): void {
    $this->actingAs(AdminFixtures::user(Role::Editor));
    $category = CategoryModel::query()->where('slug', 'الاقتصاد')->sole();

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm(['icon' => 'briefcase'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->refresh()->icon)->toBe('briefcase');
});

it('records the print section of an article through the use case, with a revision', function (): void {
    $writer = AdminFixtures::user(Role::Writer);
    $article = AdminFixtures::article(ArticleStatus::Draft, $writer);
    $this->actingAs($writer);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['print_section' => 'حوار'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($article->refresh()->print_section)->toBe('حوار')
        ->and(DB::table('article_revisions')->where('article_id', $article->id)->orderByDesc('id')->value('snapshot'))->toContain('print_section');
});
