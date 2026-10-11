<?php

declare(strict_types=1);

use Elaf\Content\Application\Author\AuthorData;
use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Application\Category\CategoryData;
use Elaf\Content\Application\Category\CategoryRepository;
use Elaf\Content\Application\Series\SeriesData;
use Elaf\Content\Application\Series\SeriesRepository;
use Elaf\Content\Application\Tag\Tag;
use Elaf\Content\Application\Tag\TagQueries;
use Elaf\Content\Application\Tag\TagRepository;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentAuthorRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentSeriesRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentTagRepository;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Text\Slug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Contract\ContentFixtures;
use Tests\Support\Contract\EloquentContentFixtures;
use Tests\Support\Contract\InMemoryContentFixtures;
use Tests\Support\Fakes\InMemoryAuthorRepository;
use Tests\Support\Fakes\InMemoryCategoryRepository;
use Tests\Support\Fakes\InMemorySeriesRepository;
use Tests\Support\Fakes\InMemoryTagRepository;

uses(RefreshDatabase::class);

dataset('category repositories', [
    'in-memory' => fn (): CategoryRepository => new InMemoryCategoryRepository,
    'eloquent' => fn (): CategoryRepository => new EloquentCategoryRepository,
]);

dataset('series repositories', [
    'in-memory' => fn (): SeriesRepository => new InMemorySeriesRepository,
    'eloquent' => fn (): SeriesRepository => new EloquentSeriesRepository,
]);

dataset('author repositories', [
    'in-memory' => fn (): array => [new InMemoryAuthorRepository, new InMemoryContentFixtures],
    'eloquent' => fn (): array => [new EloquentAuthorRepository, new EloquentContentFixtures],
]);

dataset('tag repositories', [
    'in-memory' => fn (): TagRepository => new InMemoryTagRepository,
    'eloquent' => fn (): TagRepository => new EloquentTagRepository,
]);

it('stores, finds and updates categories', function (CategoryRepository $repository): void {
    $parent = $repository->create(new CategoryData('أخبار', Slug::fromText('أخبار')));
    $data = new CategoryData('محلية', Slug::fromText('محلية'), 'وصف', $parent, 20, false, true, 'عنوان', 'وصف SEO');
    $id = $repository->create($data);

    expect($repository->find($id)?->data)->toEqual($data)
        ->and($repository->find(CategoryId::fromInt(9999)))->toBeNull();

    $changed = new CategoryData('محليات', Slug::fromText('محليات'));
    $repository->update($id, $changed);
    expect($repository->find($id)?->data)->toEqual($changed);
})->with('category repositories');

it('checks category slugs, optionally ignoring one category', function (CategoryRepository $repository): void {
    $id = $repository->create(new CategoryData('أسواق', Slug::fromText('أسواق')));

    expect($repository->slugExists(Slug::fromText('أسواق')))->toBeTrue()
        ->and($repository->slugExists(Slug::fromText('أسواق'), $id))->toBeFalse()
        ->and($repository->slugExists(Slug::fromText('تقارير')))->toBeFalse();
})->with('category repositories');

it('stores, finds and updates series', function (SeriesRepository $repository): void {
    $id = $repository->create(new SeriesData('سلسلة', Slug::fromText('سلسلة'), 'وصف'));

    expect($repository->find($id)?->data)->toEqual(new SeriesData('سلسلة', Slug::fromText('سلسلة'), 'وصف'))
        ->and($repository->find(SeriesId::fromInt(9999)))->toBeNull()
        ->and($repository->slugExists(Slug::fromText('سلسلة')))->toBeTrue()
        ->and($repository->slugExists(Slug::fromText('سلسلة'), $id))->toBeFalse();

    $repository->update($id, new SeriesData('أخرى', Slug::fromText('أخرى')));
    expect($repository->find($id)?->data->title)->toBe('أخرى');
})->with('series repositories');

it('stores, finds and updates authors, also by linked user', function (AuthorRepository $repository, ContentFixtures $fx): void {
    $user = $fx->user();
    $data = new AuthorData('آمنة', Slug::fromText('آمنة'), 'كاتبة', 'نبذة', 'a.webp', true, $user, ['x' => 'https://x.com/a']);
    $id = $repository->create($data);

    expect($repository->find($id)?->data)->toEqual($data)
        ->and($repository->findByUser($user)?->id)->toEqual($id)
        ->and($repository->findByUser(UserId::fromInt(9999)))->toBeNull()
        ->and($repository->find(AuthorId::fromInt(9999)))->toBeNull()
        ->and($repository->slugExists(Slug::fromText('آمنة'), $id))->toBeFalse();

    $repository->update($id, new AuthorData('آمنة', Slug::fromText('آمنة')));
    expect($repository->find($id)?->data->socialLinks)->toBe([])
        ->and($repository->findByUser($user))->toBeNull();
})->with('author repositories');

it('stores tags and finds them by id and slug', function (TagRepository&TagQueries $repository): void {
    $id = $repository->create('الذهب', Slug::fromText('الذهب'));

    expect($repository->find($id))->toEqual(new Tag($id, 'الذهب', Slug::fromText('الذهب')))
        ->and($repository->findBySlug(Slug::fromText('الذهب'))?->id)->toEqual($id)
        ->and($repository->findBySlug(Slug::fromText('الفضة')))->toBeNull()
        ->and($repository->find(TagId::fromInt(9999)))->toBeNull();
})->with('tag repositories');

it('suggests tags by name prefix, alphabetically and limited', function (TagRepository&TagQueries $repository): void {
    foreach (['الدولار', 'الذهب', 'الدين العام', 'النفط', '100%_نسبة'] as $i => $name) {
        $repository->create($name, Slug::fromString("tag-{$i}"));
    }

    $names = static fn (array $tags): array => array_map(static fn (Tag $t): string => $t->name, $tags);

    expect($names($repository->suggest('الد')))->toBe(['الدولار', 'الدين العام'])
        ->and($names($repository->suggest('ال', 2)))->toHaveCount(2)
        ->and($names($repository->suggest('100%')))->toBe(['100%_نسبة'])
        ->and($repository->suggest('%'))->toBe([])
        ->and($repository->suggest(''))->toBe([]);
})->with('tag repositories');
