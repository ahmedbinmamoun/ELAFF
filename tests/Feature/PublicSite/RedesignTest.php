<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Identity\Domain\UserId;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRegistry;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRenderer;
use Elaf\Layout\Presentation\Homepage\HomepageContext;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\PublicFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    $this->fx = new PublicFixtures;
    $this->sudan = CategoryModel::query()->where('slug', 'أخبار-السودان')->sole();
    $this->author = AuthorModel::query()->create(['name' => 'كاتب', 'slug' => 'katib']);
});

it('composes the homepage from the configured blocks, in order, skipping unknown types (FR-HOME-01/02)', function (): void {
    foreach (range(1, 8) as $i) {
        $this->fx->published("خبر {$i}", $this->sudan->id, [$this->author->id], image: "demo/{$i}.jpg");
    }

    config(['homepage.blocks' => [
        ['type' => 'most_read', 'region' => 'top', 'options' => ['limit' => 3, 'days' => 0]],
        ['type' => 'no_such_block', 'region' => 'top'],
        ['type' => 'hero', 'region' => 'main', 'options' => ['secondary' => 2]],
        ['type' => 'latest_articles', 'region' => 'main', 'options' => ['featured' => 1, 'strip' => 2]],
    ]]);

    $html = $this->get('/')->assertOk()->assertSeeInOrder([__('public.most_read'), 'fetchpriority="high"', __('public.latest')], false)->getContent();

    // Hero shows the 3 newest (8, 7, 6); latest continues without repeating them.
    expect(substr_count(explode(__('public.latest'), $html)[0], 'خبر 8<'))->toBeGreaterThanOrEqual(1)
        ->and($html)->toContain('خبر 5<', 'خبر 3<');
});

it('accepts a new block type with one class and one registration (FR-HOME-03)', function (): void {
    $this->app->make(HomepageBlockRegistry::class)->register(new class implements HomepageBlockRenderer
    {
        public function type(): string
        {
            return 'promo';
        }

        public function render(array $options, HomepageContext $context): ?View
        {
            return view('homepage.partials.sections-nav');
        }
    });
    config(['homepage.blocks' => [['type' => 'promo', 'region' => 'bottom']]]);

    $this->get('/')->assertOk()->assertSee(__('public.paper_sections'));
});

it('lists saved favourites in the order saved, skipping unknown and unpublished ids', function (): void {
    $a = $this->fx->published('أول', $this->sudan->id, [$this->author->id]);
    $b = $this->fx->published('ثان', $this->sudan->id, [$this->author->id]);
    $draft = $this->fx->published('مسودة', $this->sudan->id, [$this->author->id], publish: false);

    $this->get('/favorites')->assertOk()->assertSee('noindex', false)->assertSee('favoritesPage', false);

    $this->get("/favorites/cards?ids={$b->id},999,{$a->id},{$draft->id},abc")
        ->assertOk()
        ->assertSeeInOrder(['ثان', 'أول'])
        ->assertDontSee('مسودة');

    expect(trim($this->get('/favorites/cards?ids=')->getContent()))->toBe('');
});

it('ranks "most read" by views, then by recency', function (): void {
    $quiet = $this->fx->published('هادئ', $this->sudan->id, [$this->author->id]);
    $popular = $this->fx->published('الأكثر شعبية', $this->sudan->id, [$this->author->id]);
    $fresh = $this->fx->published('جديد', $this->sudan->id, [$this->author->id]);
    ArticleModel::query()->whereKey($quiet->id)->update(['views_count' => 50]);
    ArticleModel::query()->whereKey($popular->id)->update(['views_count' => 900]);

    $titles = array_map(static fn ($c) => $c->title, app(Elaf\Content\Application\Query\ArticleQueries::class)->mostRead(3));

    expect($titles)->toBe(['الأكثر شعبية', 'هادئ', 'جديد']);
});

it('shows the mobile shell: section nav with "المزيد", search, ticker and the tab bar', function (): void {
    $this->fx->published('عاجل الآن', $this->sudan->id, [$this->author->id], then: static fn (Article $a, FrozenClock $c, UserId $u) => $a->markBreaking(null, $u, $c));
    CategoryModel::query()->where('slug', 'منوعات')->update(['is_visible' => true]);

    $this->get('/')
        ->assertOk()
        ->assertSee('viewport-fit=cover', false)
        ->assertSee(__('public.more'))
        ->assertSee(route('category.show', 'منوعات'), false)
        ->assertSee('action="'.route('search').'"', false)
        ->assertSee('x-data="ticker(1)"', false)
        ->assertSee('عاجل الآن')
        ->assertSeeInOrder([route('home'), route('issues.index'), route('favorites'), url('/writer/login')], false)
        ->assertSee('md:hidden', false)
        ->assertSee('env(safe-area-inset-bottom)', false);
});

it('serves styled placeholders for issues and search until their phases', function (): void {
    $this->get('/issues')->assertOk()->assertSee(__('public.issues_page.title'));
    $this->get('/search?q='.urlencode('الذهب'))->assertOk()->assertSee(__('public.search_page.query', ['q' => 'الذهب']))->assertSee('noindex', false);
});

it('marks images lazy except the hero, always with dimensions', function (): void {
    foreach (range(1, 5) as $i) {
        $this->fx->published("صورة {$i}", $this->sudan->id, [$this->author->id], image: "demo/{$i}.jpg");
    }

    $html = $this->get('/')->getContent();
    preg_match_all('/<img [^>]*>/', $html, $images);

    expect($images[0])->not->toBeEmpty();
    foreach ($images[0] as $i => $img) {
        expect($img)->toContain('width="', 'height="');
        $i === 0 ? expect($img)->toContain('fetchpriority="high"') : expect($img)->toContain('loading="lazy"');
    }
});
