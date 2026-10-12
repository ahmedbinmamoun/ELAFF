<?php

declare(strict_types=1);

use Elaf\Layout\Application\Menu\MenuItemData;
use Elaf\Layout\Application\Menu\MenuRepository;
use Elaf\Layout\Domain\Menu\MenuItemType;
use Elaf\Layout\Domain\Menu\MenuKey;
use Elaf\Layout\Infrastructure\Persistence\EloquentMenuRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\InMemoryMenus;

uses(RefreshDatabase::class);

dataset('menu repositories', [
    'in-memory' => fn (): MenuRepository => new InMemoryMenus,
    'eloquent' => fn (): MenuRepository => new EloquentMenuRepository(app(ConnectionInterface::class)),
]);

it('replaces a menu and returns it in order, menus kept apart', function (MenuRepository $menus): void {
    $tree = [
        new MenuItemData('أ', MenuItemType::Issues, null, null, 'newspaper', true, [
            new MenuItemData('أ1', MenuItemType::Url, null, '/letters', null, false),
            new MenuItemData('أ2', MenuItemType::Url, null, 'https://example.com', null, true),
        ]),
        new MenuItemData('ب', MenuItemType::Category, 5, null, null, true),
    ];

    $menus->replace(MenuKey::Header, [new MenuItemData('قديم', MenuItemType::Issues, null, null, null, true)]);
    $menus->replace(MenuKey::Header, $tree);
    $menus->replace(MenuKey::Footer, [new MenuItemData('ت', MenuItemType::Issues, null, null, null, true)]);

    expect($menus->items(MenuKey::Header))->toEqual($tree)
        ->and($menus->items(MenuKey::Footer))->toHaveCount(1)
        ->and($menus->items(MenuKey::Sections))->toBe([]);
})->with('menu repositories');
