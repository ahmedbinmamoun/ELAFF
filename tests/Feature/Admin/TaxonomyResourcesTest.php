<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Infrastructure\Persistence\Models\SeriesModel;
use Elaf\Content\Infrastructure\Persistence\Models\TagModel;
use Elaf\Content\Presentation\Filament\Resources\Authors\Pages\CreateAuthor;
use Elaf\Content\Presentation\Filament\Resources\Authors\Pages\ListAuthors;
use Elaf\Content\Presentation\Filament\Resources\Categories\Pages\CreateCategory;
use Elaf\Content\Presentation\Filament\Resources\Categories\Pages\EditCategory;
use Elaf\Content\Presentation\Filament\Resources\Categories\Pages\ListCategories;
use Elaf\Content\Presentation\Filament\Resources\Series\Pages\CreateSeries;
use Elaf\Content\Presentation\Filament\Resources\Tags\Pages\CreateTag;
use Elaf\Content\Presentation\Filament\Resources\Tags\Pages\EditTag;
use Elaf\Content\Presentation\Filament\Resources\Tags\Pages\ListTags;
use Elaf\Identity\Application\Role;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    Filament::setCurrentPanel('admin');
    $this->actingAs(AdminFixtures::user(Role::Editor));
});

it('creates and edits categories through the use cases (FR-TAX-01)', function (): void {
    Livewire::test(CreateCategory::class)
        ->fillForm(['name' => 'اقتصاد دولي', 'sort_order' => 130, 'is_open_to_contributors' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    $category = CategoryModel::query()->where('slug', 'اقتصاد-دولي')->sole();
    expect($category->is_open_to_contributors)->toBeTrue();

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm(['slug' => 'الاقتصاد'])
        ->call('save')
        ->assertHasFormErrors(['slug']);

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm(['parent_id' => CategoryModel::query()->where('slug', 'أخبار-السودان')->value('id')])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($category->refresh()->parent_id)->not->toBeNull();
});

it('refuses to delete a category that still has articles', function (): void {
    $used = AdminFixtures::article(ArticleStatus::Draft, AdminFixtures::user(Role::Writer))->category;
    $empty = CategoryModel::query()->where('slug', 'منوعات')->sole();

    Livewire::test(ListCategories::class)
        ->callAction(TestAction::make('delete')->table($used))
        ->assertNotified((string) __('errors.category_has_articles'));
    expect(CategoryModel::query()->find($used?->id))->not->toBeNull();

    Livewire::test(ListCategories::class)->callAction(TestAction::make('delete')->table($empty));
    expect(CategoryModel::query()->find($empty->id))->toBeNull();
});

it('creates, renames and deletes tags (FR-TAX-02)', function (): void {
    Livewire::test(CreateTag::class)->fillForm(['name' => 'الذهب'])->call('create')->assertHasNoFormErrors();
    $tag = TagModel::query()->where('slug', 'الذهب')->sole();

    Livewire::test(EditTag::class, ['record' => $tag->getRouteKey()])->fillForm(['name' => 'أسعار الذهب'])->call('save')->assertHasNoFormErrors();
    expect($tag->refresh()->slug)->toBe('أسعار-الذهب');

    Livewire::test(ListTags::class)->callAction(TestAction::make('delete')->table($tag));
    expect(TagModel::query()->count())->toBe(0);
});

it('creates series (FR-TAX-03) and authors (FR-AUT-01)', function (): void {
    Livewire::test(CreateSeries::class)->fillForm(['title' => 'كيف نفهم الحرب والسلام'])->call('create')->assertHasNoFormErrors();
    expect(SeriesModel::query()->value('slug'))->toBe('كيف-نفهم-الحرب-والسلام');

    Livewire::test(CreateAuthor::class)
        ->fillForm(['name' => 'آمنة الطيب', 'is_columnist' => true, 'social_links' => ['x' => 'https://x.com/amna']])
        ->call('create')
        ->assertHasNoFormErrors();
    $author = AuthorModel::query()->sole();
    expect($author->is_columnist)->toBeTrue()->and($author->social_links)->toBe(['x' => 'https://x.com/amna']);

    Livewire::test(CreateAuthor::class)
        ->fillForm(['name' => 'كاتب', 'social_links' => ['x' => 'not a url']])
        ->call('create')
        ->assertHasFormErrors(['social_links']);
});

it('keeps an author who signs articles', function (): void {
    $article = AdminFixtures::article(ArticleStatus::Published, AdminFixtures::user(Role::Writer));
    $author = $article->authors->first();

    Livewire::test(ListAuthors::class)
        ->callAction(TestAction::make('delete')->table($author))
        ->assertNotified((string) __('errors.author_has_articles'));

    expect(AuthorModel::query()->find($author?->id))->not->toBeNull();
});
