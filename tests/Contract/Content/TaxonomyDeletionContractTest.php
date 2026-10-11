<?php

declare(strict_types=1);

use Elaf\Content\Application\Author\AuthorData;
use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Application\Category\CategoryData;
use Elaf\Content\Application\Category\CategoryRepository;
use Elaf\Content\Application\Series\SeriesData;
use Elaf\Content\Application\Series\SeriesRepository;
use Elaf\Content\Application\Tag\TagRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentAuthorRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentSeriesRepository;
use Elaf\Content\Infrastructure\Persistence\Eloquent\EloquentTagRepository;
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

dataset('categories with fixtures', [
    'in-memory' => fn (): array => [new InMemoryCategoryRepository, new InMemoryContentFixtures],
    'eloquent' => fn (): array => [new EloquentCategoryRepository, new EloquentContentFixtures],
]);

dataset('series with fixtures', [
    'in-memory' => fn (): array => [new InMemorySeriesRepository, new InMemoryContentFixtures],
    'eloquent' => fn (): array => [new EloquentSeriesRepository, new EloquentContentFixtures],
]);

dataset('authors with fixtures', [
    'in-memory' => fn (): array => [new InMemoryAuthorRepository, new InMemoryContentFixtures],
    'eloquent' => fn (): array => [new EloquentAuthorRepository, new EloquentContentFixtures],
]);

dataset('tags', [
    'in-memory' => fn (): TagRepository => new InMemoryTagRepository,
    'eloquent' => fn (): TagRepository => new EloquentTagRepository,
]);

it('knows whether a category has articles or subcategories, and deletes it', function (CategoryRepository $repository, ContentFixtures $fx): void {
    $parent = $repository->create(new CategoryData('أخبار', Slug::fromText('أخبار')));
    $child = $repository->create(new CategoryData('محلية', Slug::fromText('محلية'), parentId: $parent));
    $used = $repository->create(new CategoryData('تقارير', Slug::fromText('تقارير')));
    $fx->useInArticle($repository, $used);

    expect($repository->hasChildren($parent))->toBeTrue()
        ->and($repository->hasChildren($child))->toBeFalse()
        ->and($repository->hasArticles($used))->toBeTrue()
        ->and($repository->hasArticles($parent))->toBeFalse();

    $repository->delete($child);
    expect($repository->find($child))->toBeNull()
        ->and($repository->hasChildren($parent))->toBeFalse();
})->with('categories with fixtures');

it('knows whether a series has articles, and deletes it', function (SeriesRepository $repository, ContentFixtures $fx): void {
    $used = $repository->create(new SeriesData('أولى', Slug::fromText('أولى')));
    $free = $repository->create(new SeriesData('ثانية', Slug::fromText('ثانية')));
    $fx->useInArticle($repository, $used);

    expect($repository->hasArticles($used))->toBeTrue()
        ->and($repository->hasArticles($free))->toBeFalse();

    $repository->delete($free);
    expect($repository->find($free))->toBeNull();
})->with('series with fixtures');

it('knows whether an author signs articles, and deletes them keeping the slug reserved', function (AuthorRepository $repository, ContentFixtures $fx): void {
    $used = $repository->create(new AuthorData('أول', Slug::fromText('أول')));
    $free = $repository->create(new AuthorData('ثان', Slug::fromText('ثان')));
    $fx->useInArticle($repository, $used);

    expect($repository->hasArticles($used))->toBeTrue()
        ->and($repository->hasArticles($free))->toBeFalse();

    $repository->delete($free);
    expect($repository->find($free))->toBeNull();
})->with('authors with fixtures');

it('renames and deletes tags', function (TagRepository $repository): void {
    $id = $repository->create('الذهب', Slug::fromText('الذهب'));
    $repository->rename($id, 'أسعار الذهب', Slug::fromText('أسعار الذهب'));

    expect($repository->find($id)?->name)->toBe('أسعار الذهب')
        ->and($repository->findBySlug(Slug::fromText('الذهب')))->toBeNull()
        ->and($repository->findBySlug(Slug::fromText('أسعار الذهب'))?->id)->toEqual($id);

    $repository->delete($id);
    expect($repository->find($id))->toBeNull();
})->with('tags');
