<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\CreateArticle;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\EditArticle;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\ListArticles;
use Elaf\Content\Presentation\Filament\Resources\Articles\Pages\ViewArticle;
use Elaf\Identity\Application\Role;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    Filament::setCurrentPanel('admin');
    $this->writer = AdminFixtures::user(Role::Writer);
    $this->author = AdminFixtures::author();
    $this->form = fn (array $overrides = []): array => [
        'title' => 'عنوان من النموذج',
        'body' => '<p>نص المقال</p>',
        'source_name' => 'إيلاف',
        'category_id' => CategoryModel::query()->value('id'),
        'author_ids' => [$this->author->id],
        'tags' => ['اقتصاد'],
        ...$overrides,
    ];
});

it('shows writers only their own articles and editors all of them', function (): void {
    $mine = AdminFixtures::article(ArticleStatus::Draft, $this->writer, title: 'مقالي');
    $theirs = AdminFixtures::article(ArticleStatus::Draft, AdminFixtures::user(Role::Writer), title: 'مقال غيري');

    $this->actingAs($this->writer);
    Livewire::test(ListArticles::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$theirs]);

    $this->actingAs(AdminFixtures::user(Role::Editor));
    Livewire::test(ListArticles::class)->assertCanSeeTableRecords([$mine, $theirs]);
});

it('creates a draft through the use case, sanitizing the body (FR-ART-01..03)', function (): void {
    $this->actingAs($this->writer);

    Livewire::test(CreateArticle::class)
        ->fillForm(($this->form)(['body' => '<h2 onclick="x()">عنوان</h2><script>alert(1)</script><p>نص</p>']))
        ->call('create')
        ->assertHasNoFormErrors();

    $article = ArticleModel::query()->with('tags')->sole();
    expect($article->status)->toBe('draft')
        ->and($article->created_by)->toBe($this->writer->id)
        ->and($article->slug)->toBe('عنوان-من-النموذج')
        ->and($article->body)->toBe('<h2>عنوان</h2><p>نص</p>')
        ->and($article->tags->pluck('name')->all())->toBe(['اقتصاد'])
        ->and(DB::table('article_revisions')->count())->toBe(1);
});

it('reports use-case validation on the right field, in Arabic', function (array $overrides, string $field): void {
    $this->actingAs($this->writer);

    Livewire::test(CreateArticle::class)
        ->fillForm(($this->form)($overrides))
        ->call('create')
        ->assertHasFormErrors([$field]);

    expect(ArticleModel::query()->count())->toBe(0);
})->with([
    'malformed slug' => [['slug' => 'Bad Slug'], 'slug'],
    'bad canonical url' => [['canonical_url' => 'javascript:x'], 'canonical_url'],
    'too many tags' => [['tags' => array_map(static fn (int $i): string => "وسم {$i}", range(1, 21))], 'tags'],
]);

it('stores a captioned image from the editor as <figure> and shows it back as a block (FR-ART-02)', function (): void {
    $this->actingAs($this->writer);
    $block = '<div data-type="customBlock" data-id="figure" data-config=\'{"image":"articles/inline/a.webp","alt":"وصف","caption":"تعليق الصورة"}\'></div>';

    Livewire::test(CreateArticle::class)
        ->fillForm(($this->form)(['body' => '<p>قبل</p>'.$block]))
        ->call('create')
        ->assertHasNoFormErrors();

    $article = ArticleModel::query()->sole();
    expect($article->body)->toContain('<figure><img src="/storage/articles/inline/a.webp" alt="وصف" /><figcaption>تعليق الصورة</figcaption></figure>');

    $body = Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])->get('data.body');
    expect(json_encode($body, JSON_UNESCAPED_UNICODE))->toContain('customBlock')->toContain('تعليق الصورة');
});

it('keeps tables in the body', function (): void {
    $this->actingAs($this->writer);
    $table = '<table><tbody><tr><th><p>البند</p></th><th><p>القيمة</p></th></tr><tr><td><p>القمح</p></td><td><p>120</p></td></tr></tbody></table>';

    Livewire::test(CreateArticle::class)->fillForm(($this->form)(['body' => $table]))->call('create')->assertHasNoFormErrors();

    expect(ArticleModel::query()->sole()->body)->toContain('<table>')->toContain('<th>')->toContain('القمح');
});

it('lets the writer edit their draft and records the change', function (): void {
    $article = AdminFixtures::article(ArticleStatus::Draft, $this->writer);
    $this->actingAs($this->writer);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->assertSchemaStateSet(['title' => 'مقال للاختبار'])
        ->fillForm(['title' => 'عنوان معدل'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($article->refresh()->title)->toBe('عنوان معدل')
        ->and(DB::table('activity_log')->where('event', 'updated')->count())->toBe(1);
});

it('locks the article for its writer once submitted, leaving it viewable (FR-CON-07)', function (): void {
    $article = AdminFixtures::article(ArticleStatus::InReview, $this->writer);
    $this->actingAs($this->writer);

    $this->get("/admin/articles/{$article->id}/edit")->assertForbidden();
    $this->get("/admin/articles/{$article->id}")->assertOk();
    Livewire::test(ViewArticle::class, ['record' => $article->getRouteKey()])->assertSchemaStateSet(['title' => 'مقال للاختبار']);
});

it('keeps writers out of other writers\' articles', function (): void {
    $theirs = AdminFixtures::article(ArticleStatus::Draft, AdminFixtures::user(Role::Writer));
    $this->actingAs($this->writer);

    $this->get("/admin/articles/{$theirs->id}")->assertNotFound();
    $this->get("/admin/articles/{$theirs->id}/edit")->assertNotFound();
});

it('lets editors edit any article in any state', function (): void {
    $article = AdminFixtures::article(ArticleStatus::Published, $this->writer);
    $this->actingAs(AdminFixtures::user(Role::Editor));

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['subtitle' => 'تصحيح'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($article->refresh()->subtitle)->toBe('تصحيح')
        ->and($article->status)->toBe('published');
});

it('never deletes articles from the admin', function (): void {
    $article = AdminFixtures::article(ArticleStatus::Draft, $this->writer);
    $this->actingAs(AdminFixtures::user(Role::Admin));

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])->assertActionDoesNotExist('delete');
});
