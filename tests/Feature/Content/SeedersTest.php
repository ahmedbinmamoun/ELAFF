<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoContentSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Application\ContentPermission as P;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Identity\Application\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('grants each role exactly its PRD §2 permissions', function (Role $role, array $allowed): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create()->assignRole($role->value);

    foreach (P::cases() as $permission) {
        expect(Gate::forUser($user)->allows($permission->value))
            ->toBe(in_array($permission, $allowed, true), "{$role->value} / {$permission->value}");
    }
})->with([
    'writer' => [Role::Writer, [P::CreateArticles]],
    'contributor' => [Role::Contributor, [P::CreateArticles]],
    'editor' => [Role::Editor, [P::CreateArticles, P::EditAnyArticle, P::ReviewArticles, P::ManageTaxonomy, P::ManageAuthors]],
    'editor-in-chief' => [Role::EditorInChief, [P::CreateArticles, P::EditAnyArticle, P::ReviewArticles, P::ManageTaxonomy, P::ManageAuthors, P::PublishArticles, P::PromoteArticles]],
    'admin' => [Role::Admin, P::cases()],
]);

it('seeds the paper\'s twelve sections once, without overwriting edits (FR-TAX-01)', function (): void {
    $this->seed(CategorySeeder::class);
    CategoryModel::query()->where('slug', 'أسواق')->update(['name' => 'الأسواق المالية']);
    $this->seed([CategorySeeder::class, RolesAndPermissionsSeeder::class, RolesAndPermissionsSeeder::class]);

    expect(CategoryModel::query()->orderBy('sort_order')->pluck('slug')->all())->toBe([
        'أخبار', 'أخبار-الأسبوع', 'أسواق', 'تقارير', 'ما-وراء-الأخبار', 'تحقيق', 'حوار', 'رأي-اقتصادي', 'رأي-سياسي', 'من-صحافة-العالم', 'بريد-القراء', 'أخيرة',
    ])
        ->and(CategoryModel::query()->where('slug', 'أسواق')->value('name'))->toBe('الأسواق المالية')
        ->and(CategoryModel::query()->where('is_open_to_contributors', true)->pluck('slug')->all())->toBe(['رأي-اقتصادي', 'رأي-سياسي'])
        ->and(DB::table('roles')->count())->toBe(count(Role::cases()));
});

it('seeds demo content in every workflow state, once', function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class, DemoContentSeeder::class, DemoContentSeeder::class]);

    expect(ArticleModel::query()->count())->toBe(40)
        ->and(ArticleModel::query()->distinct()->pluck('status')->sort()->values()->all())
        ->toBe(['approved', 'draft', 'in_review', 'published', 'returned', 'scheduled'])
        ->and(User::query()->where('email', 'editor@elaf.test')->first()?->hasRole('editor'))->toBeTrue();
});
