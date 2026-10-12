<?php

declare(strict_types=1);

use Elaf\Settings\Application\Page\PageData;
use Elaf\Settings\Application\Page\PageRepository;
use Elaf\Settings\Application\Site\SettingsStore;
use Elaf\Settings\Domain\Page\PageId;
use Elaf\Settings\Infrastructure\Persistence\EloquentPageRepository;
use Elaf\Settings\Infrastructure\Persistence\EloquentSettingsStore;
use Elaf\Shared\Domain\Text\Slug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\InMemoryPageRepository;
use Tests\Support\Fakes\InMemorySettings;

uses(RefreshDatabase::class);

dataset('settings stores', [
    'in-memory' => fn (): SettingsStore => new InMemorySettings,
    'eloquent' => fn (): SettingsStore => new EloquentSettingsStore,
]);

dataset('page repositories', [
    'in-memory' => fn (): PageRepository => new InMemoryPageRepository,
    'eloquent' => fn (): PageRepository => new EloquentPageRepository,
]);

it('upserts settings by key and keeps their types', function (SettingsStore $store): void {
    $store->put(['name' => 'إيلاف', 'contributors_editor_can_publish' => true, 'motto' => null]);
    $store->put(['name' => 'إيلاف السودان']);

    expect($store->all())->toEqual(['name' => 'إيلاف السودان', 'contributors_editor_can_publish' => true, 'motto' => null]);
})->with('settings stores');

it('round-trips pages and checks slugs', function (PageRepository $pages): void {
    $id = $pages->create(new PageData('من نحن', Slug::fromString('about'), '<p>نص</p>', true, 'عنوان', 'وصف'));
    $other = $pages->create(new PageData('اتصل بنا', Slug::fromString('contact'), '<p>.</p>', false));

    expect($pages->find($id)?->data)->toEqual(new PageData('من نحن', Slug::fromString('about'), '<p>نص</p>', true, 'عنوان', 'وصف'))
        ->and($pages->slugExists(Slug::fromString('about')))->toBeTrue()
        ->and($pages->slugExists(Slug::fromString('about'), $id))->toBeFalse()
        ->and($pages->slugExists(Slug::fromString('about'), $other))->toBeTrue();

    $pages->update($id, new PageData('عن إيلاف', Slug::fromString('about-elaf'), '<p>.</p>', false));
    expect($pages->find($id)?->data->title)->toBe('عن إيلاف');

    $pages->delete($id);
    expect($pages->find($id))->toBeNull()->and($pages->find(PageId::fromInt(9999)))->toBeNull();
})->with('page repositories');
