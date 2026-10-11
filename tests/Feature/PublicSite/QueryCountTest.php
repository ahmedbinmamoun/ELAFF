<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Infrastructure\Persistence\Models\SeriesModel;
use Elaf\Content\Infrastructure\Persistence\Models\TagModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\PublicFixtures;

uses(RefreshDatabase::class);

/*
 * Phase 3 acceptance: no N+1. Each key page runs a fixed number of queries,
 * the same with twice as much content.
 */

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    $this->fx = new PublicFixtures;
    $this->categories = CategoryModel::query()->orderBy('sort_order')->pluck('id')->all();
    $this->authors = [
        AuthorModel::query()->create(['name' => 'كاتب أول', 'slug' => 'katib-1', 'is_columnist' => true])->id,
        AuthorModel::query()->create(['name' => 'كاتب ثان', 'slug' => 'katib-2', 'is_columnist' => true])->id,
        AuthorModel::query()->create(['name' => 'كاتب ثالث', 'slug' => 'katib-3'])->id,
    ];
    $this->tags = [TagModel::query()->create(['name' => 'وسم', 'slug' => 'wasm'])->id];
    $this->series = SeriesModel::query()->create(['title' => 'سلسلة', 'slug' => 'silsila'])->id;
    $this->part = 0;

    $this->addContent = function (int $count): int {
        $last = 0;
        foreach (range(1, $count) as $i) {
            $last = $this->fx->published(
                'مقال '.uniqid(),
                $this->categories[$i % count($this->categories)],
                [$this->authors[$i % 3], $this->authors[($i + 1) % 3]],
                tags: $this->tags,
                series: [$this->series, ++$this->part],
                image: 'articles/featured/'.$i.'.webp',
            )->id;
        }

        return $last;
    };

    $this->queries = function (string $path): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($path)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };
});

it('runs a constant, small number of queries per page', function (string $page, int $max): void {
    $article = ($this->addContent)(24);
    $paths = [
        'home' => '/',
        'article' => route('article.show', ['id' => $article, 'slug' => Elaf\Content\Infrastructure\Persistence\Models\ArticleModel::query()->findOrFail($article)->slug]),
        'category' => '/category/أخبار',
        'author' => '/author/katib-1',
        'tag' => '/tag/wasm',
        'series' => '/series/silsila',
        'feed' => '/feed',
    ];

    $before = ($this->queries)($paths[$page]);
    ($this->addContent)(24);
    $after = ($this->queries)($paths[$page]);

    expect($after)->toBe($before)->and($before)->toBeLessThanOrEqual($max);
})->with([
    'home' => ['home', 12],
    'article' => ['article', 8],
    'category' => ['category', 6],
    'author' => ['author', 6],
    'tag' => ['tag', 6],
    'series' => ['series', 5],
    'feed' => ['feed', 4],
]);
