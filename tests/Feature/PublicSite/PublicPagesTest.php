<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Infrastructure\Persistence\Models\SeriesModel;
use Elaf\Content\Infrastructure\Persistence\Models\TagModel;
use Elaf\Identity\Domain\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\PublicFixtures;

uses(RefreshDatabase::class);

/**
 * The page without its "most read" aside, which may list any article.
 */
function main_column(string $html): string
{
    return explode('aria-labelledby="most-read-title"', $html)[0];
}

beforeEach(function (): void {
    $this->seed([Database\Seeders\RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    $this->fx = new PublicFixtures;
    $this->news = CategoryModel::query()->where('slug', 'أخبار-السودان')->sole();
    $this->markets = CategoryModel::query()->where('slug', 'الاقتصاد')->sole();
    $this->columnist = AuthorModel::query()->create(['name' => 'آمنة الطيب', 'slug' => 'آمنة-الطيب', 'is_columnist' => true, 'title' => 'كاتبة عمود']);
    $this->reporter = AuthorModel::query()->create(['name' => 'محمد عثمان', 'slug' => 'محمد-عثمان']);
});

it('renders the homepage: breaking ticker, hero, latest, columnists and sections (PRD §3.5)', function (): void {
    $this->fx->published('خبر عاجل مهم', $this->news->id, [$this->reporter->id], then: static fn (Article $a, FrozenClock $c, UserId $u) => $a->markBreaking(null, $u, $c));
    $this->fx->published('عمود الأسبوع', $this->markets->id, [$this->columnist->id]);
    $this->fx->published('القصة الرئيسية', $this->news->id, [$this->reporter->id], image: 'articles/featured/lead.webp',
        then: static fn (Article $a, FrozenClock $c, UserId $u) => $a->promote(true, 10, $u, $c));
    $this->fx->published('مسودة لم تنشر', $this->news->id, [$this->reporter->id], publish: false);

    $this->get('/')
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSeeInOrder([__('public.breaking'), 'خبر عاجل مهم', 'القصة الرئيسية', __('public.columnists'), 'آمنة الطيب', 'عمود الأسبوع'])
        ->assertSee('fetchpriority="high"', false)
        ->assertSee(route('category.show', 'الاقتصاد'), false)
        ->assertDontSee('مسودة لم تنشر');
});

it('hides expired breaking news from the ticker', function (): void {
    $this->fx->published('عاجل منتهي', $this->news->id, [$this->reporter->id], then: static function (Article $a, FrozenClock $c, UserId $u): void {
        $a->markBreaking($c->now()->modify('+1 hour'), $u, $c);
    });

    $this->get('/')->assertDontSee(__('public.breaking').'</h2>', false);
});

it('shows a full article with byline, date, image, share bar (WhatsApp first), tags and series navigation', function (): void {
    $series = SeriesModel::query()->create(['title' => 'كيف نفهم الحرب والسلام', 'slug' => 'كيف-نفهم-الحرب-والسلام']);
    $tag = TagModel::query()->create(['name' => 'الذهب', 'slug' => 'الذهب']);
    $first = $this->fx->published('الجزء الأول', $this->news->id, [$this->columnist->id], series: [$series->id, 1]);
    $second = $this->fx->published('الجزء الثاني', $this->news->id, [$this->columnist->id, $this->reporter->id], tags: [$tag->id], series: [$series->id, 2], image: 'a.webp');
    $third = $this->fx->published('الجزء الثالث', $this->news->id, [$this->columnist->id], series: [$series->id, 3]);

    $this->get(route('article.show', ['id' => $second->id, 'slug' => $second->slug]))
        ->assertOk()
        ->assertSee('<h1', false)
        ->assertSee('الجزء الثاني')
        ->assertSeeInOrder(['آمنة الطيب', 'محمد عثمان'])
        ->assertSee('1 مارس 2026', false)
        ->assertSee('/storage/a.webp', false)
        ->assertSee('<table>', false)
        ->assertSeeInOrder([__('public.share_whatsapp'), __('public.share_facebook'), __('public.share_x'), __('public.copy_link')])
        ->assertSee(route('tag.show', 'الذهب'), false)
        ->assertSee(__('public.series_part', ['part' => 2, 'parts' => 3]))
        ->assertSee(route('article.show', ['id' => $first->id, 'slug' => $first->slug]), false)
        ->assertSee(route('article.show', ['id' => $third->id, 'slug' => $third->slug]), false)
        ->assertSee('<link rel="canonical" href="'.route('article.show', ['id' => $second->id, 'slug' => $second->slug]).'">', false);
});

it('redirects a missing or outdated slug to the canonical URL with 301 (FR-ART-10)', function (?string $slug): void {
    $article = $this->fx->published('عنوان أصلي', $this->news->id, [$this->reporter->id]);

    $this->get('/article/'.$article->id.($slug === null ? '' : '/'.rawurlencode($slug)))
        ->assertStatus(301)
        ->assertRedirect(route('article.show', ['id' => $article->id, 'slug' => 'عنوان-أصلي']));
})->with([null, 'slug-قديم']);

it('answers 404 for articles that are not published', function (): void {
    $draft = $this->fx->published('غير منشور', $this->news->id, [$this->reporter->id], publish: false);
    $unpublished = $this->fx->published('ملغى نشره', $this->news->id, [$this->reporter->id], then: static fn (Article $a, FrozenClock $c, UserId $u) => $a->unpublish($u, $c));

    $this->get(route('article.show', ['id' => $draft->id, 'slug' => $draft->slug]))->assertNotFound();
    $this->get(route('article.show', ['id' => $unpublished->id, 'slug' => $unpublished->slug]))->assertNotFound();
    $this->get('/article/999999')->assertNotFound();
});

it('labels contributor bylines "كاتب ضيف" when the setting is on (FR-CON-13)', function (bool $on): void {
    config(['elaf.contributors.guest_byline_label' => $on]);
    $article = $this->fx->published('مقال ضيف', $this->markets->id, [$this->reporter->id], origin: ArticleOrigin::Contributor);

    $response = $this->get(route('article.show', ['id' => $article->id, 'slug' => $article->slug]));
    $on ? $response->assertSee(__('public.guest_writer')) : $response->assertDontSee(__('public.guest_writer'));
})->with([true, false]);

it('lists a category newest first, paginated, and 404s hidden categories and pages past the end', function (): void {
    foreach (range(1, 22) as $i) {
        $this->fx->published("خبر رقم {$i}", $this->news->id, [$this->reporter->id]);
    }
    $this->fx->published('خبر في قسم آخر', $this->markets->id, [$this->reporter->id]);

    $first = $this->get('/category/أخبار-السودان')->assertOk()->assertSee(__('public.page_of', ['page' => 1, 'last' => 2]));
    expect(main_column($first->getContent()))->toContain('خبر رقم 22', 'خبر رقم 3')->not->toContain('خبر رقم 2<', 'خبر في قسم آخر');
    $this->get('/category/أخبار-السودان?page=2')->assertOk()->assertSee('خبر رقم 1')->assertSee('rel="prev"', false);
    $this->get('/category/أخبار-السودان?page=3')->assertNotFound();

    $this->news->update(['is_visible' => false]);
    $this->get('/category/أخبار-السودان')->assertNotFound();
});

it('shows author pages, the columnists index, tag pages and series pages (FR-AUT-02, FR-TAX-02/03)', function (): void {
    $tag = TagModel::query()->create(['name' => 'الدولار', 'slug' => 'الدولار']);
    $series = SeriesModel::query()->create(['title' => 'سلسلة', 'slug' => 'سلسلة', 'description' => 'وصف السلسلة']);
    $this->fx->published('عمود أول', $this->markets->id, [$this->columnist->id], tags: [$tag->id], series: [$series->id, 2]);
    $this->fx->published('عمود ثان', $this->markets->id, [$this->columnist->id], series: [$series->id, 1]);
    $this->fx->published('خبر عادي', $this->news->id, [$this->reporter->id]);

    $author = $this->get('/author/آمنة-الطيب')->assertOk()->assertSee('كاتبة عمود')->assertSeeInOrder(['عمود ثان', 'عمود أول']);
    expect(main_column($author->getContent()))->not->toContain('خبر عادي');
    $this->get('/authors')->assertOk()->assertSee('آمنة الطيب')->assertSee('عمود ثان')->assertDontSee('محمد عثمان');
    $tag = $this->get('/tag/الدولار')->assertOk()->assertSee('عمود أول');
    expect(main_column($tag->getContent()))->not->toContain('عمود ثان');
    $this->get('/series/سلسلة')->assertOk()->assertSee('وصف السلسلة')->assertSeeInOrder(['عمود ثان', 'عمود أول']);

    $this->get('/author/لا-أحد')->assertNotFound();
    $this->get('/tag/لا-شيء')->assertNotFound();
    $this->get('/series/لا-شيء')->assertNotFound();
});

it('publishes valid RSS 2.0 feeds, site-wide and per category (FR-SEO-03)', function (): void {
    $this->fx->published('خبر للخلاصة', $this->news->id, [$this->reporter->id]);
    $this->fx->published('سوق للخلاصة', $this->markets->id, [$this->reporter->id]);

    $site = $this->get('/feed')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
    $xml = simplexml_load_string($site->getContent());
    expect($xml)->not->toBeFalse()
        ->and(count($xml->channel->item))->toBe(2)
        ->and((string) $xml->channel->item[0]->title)->toBe('سوق للخلاصة');

    $category = simplexml_load_string($this->get('/feed/الاقتصاد')->assertOk()->getContent());
    expect(count($category->channel->item))->toBe(1);

    $this->get('/feed/لا-شيء')->assertNotFound();
});

it('is right-to-left with Arabic dates on every public page', function (string $path): void {
    $this->fx->published('مقال', $this->news->id, [$this->columnist->id]);

    $this->get($path)->assertOk()->assertSee('<html lang="ar" dir="rtl">', false)->assertSee('مارس 2026');
})->with(['/', '/category/أخبار-السودان', '/author/آمنة-الطيب', '/authors']);
