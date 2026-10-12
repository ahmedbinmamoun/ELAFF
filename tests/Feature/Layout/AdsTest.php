<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\HomepageBlocksSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Identity\Application\Role;
use Elaf\Layout\Application\Ad\AdInput;
use Elaf\Layout\Application\Ad\CreateAd\CreateAdCommand;
use Elaf\Layout\Application\Ad\CreateAd\CreateAdHandler;
use Elaf\Layout\Application\Homepage\AddBlock\AddHomepageBlockCommand;
use Elaf\Layout\Application\Homepage\AddBlock\AddHomepageBlockHandler;
use Elaf\Layout\Application\Homepage\HomepageBlockInput;
use Elaf\Layout\Infrastructure\Persistence\AdModel;
use Elaf\Layout\Presentation\Filament\Ads\Pages\CreateAd;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;
use Tests\Support\PublicFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    Filament::setCurrentPanel('admin');
    Storage::fake('public');
    $this->chief = AdminFixtures::user(Role::EditorInChief);
    $this->actingAs($this->chief);
    $this->ad = fn (AdInput $input): int => app(CreateAdHandler::class)->handle(new CreateAdCommand($this->chief->id, $input))->value;
    $this->page = function (string $path) {
        $this->app->forgetScopedInstances();

        return $this->get($path)->assertOk();
    };
    $fx = new PublicFixtures;
    $category = (int) CategoryModel::query()->where('slug', 'الاقتصاد')->value('id');
    $author = AuthorModel::query()->create(['name' => 'كاتب', 'slug' => 'katib'])->id;
    $this->article = $fx->published('مقال طويل', $category, [$author]);
    $this->article->forceFill(['body' => '<p>فقرة 1</p><p>فقرة 2</p><p>فقرة 3</p><p>فقرة 4</p>'])->save();
});

it('lets the Editor-in-Chief create an image ad that runs in its slot (FR-ADS-01, 02)', function (): void {
    Livewire::test(CreateAd::class)
        ->fillForm([
            'title' => 'بنك تجريبي', 'slot' => 'header_banner', 'kind' => 'image',
            'image_path' => UploadedFile::fake()->image('banner.png', 728, 90),
            'target_url' => 'https://bank.example/offer', 'weight' => 5, 'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ad = AdModel::query()->firstOrFail();
    ($this->page)('/issues')
        ->assertSee('data-ad-slot="header_banner"', false)
        ->assertSee(route('ads.go', $ad->id), false)
        ->assertSee('rel="sponsored noopener"', false)
        ->assertSee('aspect-ratio: 728 / 90', false);
});

it('counts a click and redirects to the advertiser (FR-ADS-03)', function (): void {
    $id = ($this->ad)(new AdInput('بنك', 'footer', imagePath: 'ads/a.png', targetUrl: 'https://bank.example/offer'));

    $this->get("/ads/{$id}/go")->assertRedirect('https://bank.example/offer');
    $this->get("/ads/{$id}/go")->assertRedirect('https://bank.example/offer');
    $this->get('/ads/999/go')->assertNotFound();

    expect(AdModel::query()->find($id)?->clicks_count)->toBe(2);
});

it('shows nothing for inactive, expired or future ads', function (): void {
    ($this->ad)(new AdInput('معطل', 'footer', imagePath: 'ads/a.png', targetUrl: 'https://ex.example', isActive: false));
    ($this->ad)(new AdInput('منتهي', 'footer', imagePath: 'ads/b.png', targetUrl: 'https://ex.example', endsAt: new DateTimeImmutable('-1 hour')));
    ($this->ad)(new AdInput('لاحق', 'footer', imagePath: 'ads/c.png', targetUrl: 'https://ex.example', startsAt: new DateTimeImmutable('+1 day')));

    ($this->page)('/issues')->assertDontSee('data-ad-slot', false);
});

it('places article ads beside and inside the article', function (): void {
    ($this->ad)(new AdInput('جانبي', 'article_sidebar', html: '<p>إعلان جانبي</p>'));
    ($this->ad)(new AdInput('داخلي', 'article_inline', html: '<p>إعلان داخلي</p>'));

    ($this->page)(route('article.show', ['id' => $this->article->id, 'slug' => $this->article->slug]))
        ->assertSee('إعلان جانبي')
        ->assertSeeInOrder(['فقرة 3', 'إعلان داخلي', 'فقرة 4']);
});

it('puts an ad between homepage sections with the ad_slot block (FR-HOME-02)', function (): void {
    $this->seed(HomepageBlocksSeeder::class);
    ($this->ad)(new AdInput('رئيسية', 'home_between_sections', html: '<p>إعلان الرئيسية</p>'));
    app(AddHomepageBlockHandler::class)->handle(new AddHomepageBlockCommand($this->chief->id, new HomepageBlockInput('ad_slot', 'bottom')));

    ($this->page)('/')->assertSee('إعلان الرئيسية')->assertSee('data-ad-slot="home_between_sections"', false);
});

it('keeps the ads screen for the Editor-in-Chief and Admin (PRD §2)', function (): void {
    $this->actingAs(AdminFixtures::user(Role::Editor))->get('/admin/ads')->assertForbidden();
    $this->actingAs($this->chief)->get('/admin/ads')->assertOk();
});
