<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\IssuePlacement;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Infrastructure\Persistence\IssueModel;
use Elaf\Settings\Infrastructure\CachedSettingsReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\IssueFixtures;
use Tests\Support\PublicFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    $this->fx = new PublicFixtures;
    $this->category = (int) CategoryModel::query()->orderBy('sort_order')->value('id');
    $this->author = AuthorModel::query()->create(['name' => 'كاتب', 'slug' => 'katib'])->id;
    $this->onPage = function (IssueModel $issue, int $page, string $title, bool $publish = true) {
        return $this->fx->published($title, $this->category, [$this->author], publish: $publish,
            then: static fn (Article $a, FrozenClock $clock, UserId $by) => $a->assignToIssue(new IssuePlacement(IssueId::fromInt($issue->id), $page), $by, $clock));
    };
});

it('lists ready issues newest first, the latest featured (FR-ISS-04)', function (): void {
    IssueFixtures::ready(118, '2026-09-24');
    IssueFixtures::ready(120, '2026-10-08', title: 'عدد الذكرى');
    IssueFixtures::ready(119, '2026-10-01');
    IssueFixtures::pending(121, '2026-10-15');
    IssueFixtures::failed(122);

    $this->get('/issues')->assertOk()
        ->assertSee('أحدث عدد')
        ->assertSee('عدد الذكرى')
        ->assertSeeInOrder(['العدد 120', 'العدد 119', 'العدد 118'])
        ->assertDontSee('العدد 121')
        ->assertDontSee('العدد 122')
        ->assertSee('/storage/issues/120/abcdef123456/pages/001-thumb.webp', false)
        ->assertSee(route('issues.pdf', 120), false);
});

it('filters by year and paginates (FR-ISS-04)', function (): void {
    IssueFixtures::ready(10, '2025-12-25');
    foreach (range(11, 36) as $i => $number) {
        IssueFixtures::ready($number, (new DateTimeImmutable('2026-01-01'))->modify("+{$i} weeks")->format('Y-m-d'));
    }

    $this->get('/issues?year=2025')->assertOk()->assertSee('أعداد 2025')->assertSee('العدد 10')->assertDontSee('العدد 11<', false);
    $this->get('/issues?year=2026')->assertOk()->assertDontSee('أحدث عدد')->assertSee('العدد 36')->assertDontSee('العدد 10<', false);
    $this->get('/issues?page=2')->assertOk()->assertSee('العدد 12<', false)->assertSee('العدد 10<', false)->assertDontSee('العدد 13<', false);
    $this->get('/issues?year=1999')->assertNotFound();
    $this->get('/issues?page=9')->assertNotFound();
});

it('shows an issue: cover, contents by page, every page without JS (FR-ISS-03)', function (): void {
    IssueFixtures::ready(119, '2026-10-01');
    $issue = IssueFixtures::ready(120, '2026-10-08', pages: 3);
    IssueFixtures::ready(121, '2026-10-15');
    $article = ($this->onPage)($issue, 2, 'تقرير الصفحة الثانية');
    ($this->onPage)($issue, 1, 'خبر الصفحة الأولى');
    ($this->onPage)($issue, 3, 'مسودة غير منشورة', publish: false);

    $response = $this->get('/issues/120')->assertOk()
        ->assertSee('العدد 120')
        ->assertSee('8 أكتوبر 2026')
        ->assertSeeInOrder(['في هذا العدد', 'صفحة 1', 'خبر الصفحة الأولى', 'صفحة 2', 'تقرير الصفحة الثانية'])
        ->assertDontSee('مسودة غير منشورة')
        ->assertSee(route('article.show', ['id' => $article->id, 'slug' => $article->slug]), false)
        ->assertSee('id="page-3"', false)
        ->assertSee('/storage/issues/120/abcdef123456/pages/003.webp', false)
        ->assertSee('width="1100" height="1556"', false)
        ->assertSee(route('issues.show', 119), false)
        ->assertSee(route('issues.show', 121), false)
        ->assertSee('og:image', false);

    expect(substr_count((string) $response->getContent(), 'loading="lazy"'))->toBeGreaterThanOrEqual(3);
});

it('hides issues that are not ready', function (): void {
    IssueFixtures::pending(121);
    IssueFixtures::failed(122);

    $this->get('/issues/121')->assertNotFound();
    $this->get('/issues/122')->assertNotFound();
    $this->get('/issues/121/pdf')->assertNotFound();
    $this->get('/issues/999')->assertNotFound();
});

it('counts a PDF download, then redirects to the file (PRD §9)', function (): void {
    $issue = IssueFixtures::ready(120);

    $this->get('/issues/120/pdf')->assertRedirect('/storage/issues/120/abcdef123456/elaf-120.pdf');
    $this->get('/issues/120/pdf')->assertRedirect();

    expect($issue->refresh()->downloads_count)->toBe(2);
});

it('shows the latest issue on the homepage (FR-HOME-02 current_issue)', function (): void {
    $this->fx->published('مقال', $this->category, [$this->author]);
    $this->get('/')->assertOk()->assertDontSee('العدد الحالي');

    IssueFixtures::ready(119, '2026-10-01');
    IssueFixtures::ready(120, '2026-10-08');
    IssueFixtures::pending(121, '2026-10-15');

    $this->get('/')->assertOk()->assertSee('العدد الحالي')->assertSee('العدد 120')->assertDontSee('العدد 121');
});

it('links an article to its print page once the issue is online', function (): void {
    $pending = IssueFixtures::pending(121);
    $waiting = ($this->onPage)($pending, 2, 'في عدد قيد المعالجة');
    $ready = IssueFixtures::ready(120);
    $article = ($this->onPage)($ready, 4, 'في العدد الجاهز');

    $this->get(route('article.show', ['id' => $article->id, 'slug' => $article->slug]))->assertOk()
        ->assertSee('نُشر في النسخة المطبوعة: العدد 120، صفحة 4')
        ->assertSee(route('issues.show', 120).'#page-4', false);
    $this->get(route('article.show', ['id' => $waiting->id, 'slug' => $waiting->slug]))->assertOk()
        ->assertDontSee('نُشر في النسخة المطبوعة');
});

it('runs a constant number of queries on the archive pages', function (string $path, int $max): void {
    $add = function (int $from): void {
        foreach (range($from, $from + 11) as $number) {
            $issue = IssueFixtures::ready($number, (new DateTimeImmutable('2026-01-01'))->modify("+{$number} days")->format('Y-m-d'));
            ($this->onPage)($issue, 1, 'مقال '.$number);
        }
    };
    $count = function () use ($path): int {
        $this->app->forgetScopedInstances();
        cache()->forget(CachedSettingsReader::CACHE_KEY);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($path)->assertOk();
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    $add(1);
    $before = $count();
    $add(13);
    $after = $count();

    expect($after)->toBe($before)->and($before)->toBeLessThanOrEqual($max);
})->with([
    'index' => ['/issues', 6],
    'issue' => ['/issues/5', 10],
]);
