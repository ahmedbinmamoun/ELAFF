<?php

declare(strict_types=1);

use Elaf\Layout\Application\LayoutPermission;
use Elaf\Layout\Application\Menu\MenuItemData;
use Elaf\Layout\Application\Menu\MenuItemInput as Item;
use Elaf\Layout\Application\Menu\SaveMenu\SaveMenuCommand;
use Elaf\Layout\Application\Menu\SaveMenu\SaveMenuHandler;
use Elaf\Layout\Domain\Menu\Events\MenuChanged;
use Elaf\Layout\Domain\Menu\MenuItemType;
use Elaf\Layout\Domain\Menu\MenuKey;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuthorizer;
use Tests\Support\Fakes\InMemoryMenus;
use Tests\Support\Fakes\RecordingEventDispatcher;

beforeEach(function (): void {
    $this->menus = new InMemoryMenus;
    $this->menus->categories = [1, 2];
    $this->menus->pages = [10];
    $this->authorizer = new InMemoryAuthorizer([LayoutPermission::ManageMenus->value]);
    $this->events = new RecordingEventDispatcher;
    $this->save = fn (string $menu, array $items) => (new SaveMenuHandler($this->authorizer, $this->menus, $this->menus, new ImmediateTransaction, $this->events, new FrozenClock))
        ->handle(new SaveMenuCommand(3, $menu, $items));
    $this->reason = static function (callable $work): string {
        try {
            $work();
        } catch (InvalidInput $e) {
            return $e->reason.(isset($e->params['position']) ? '@'.$e->params['position'] : '');
        }

        return 'none';
    };
});

it('saves a menu with order, one level of sub-items and visibility (FR-NAV-01)', function (): void {
    ($this->save)('header', [
        new Item(' الاقتصاد ', 'category', 1, icon: 'chart', children: [
            new Item('من نحن', 'page', 10),
            new Item('بريد القراء', 'url', url: '/letters'),
        ]),
        new Item('الأعداد', 'issues', targetId: 99, isVisible: false),
        new Item('قناتنا', 'url', url: 'https://example.com/channel'),
    ]);

    $items = $this->menus->items(MenuKey::Header);
    expect($items)->toHaveCount(3)
        ->and($items[0])->toEqual(new MenuItemData('الاقتصاد', MenuItemType::Category, 1, null, 'chart', true, [
            new MenuItemData('من نحن', MenuItemType::Page, 10, null, null, true),
            new MenuItemData('بريد القراء', MenuItemType::Url, null, '/letters', null, true),
        ]))
        ->and($items[1]->targetId)->toBeNull()
        ->and($items[1]->isVisible)->toBeFalse()
        ->and($this->events->ofType(MenuChanged::class))->toHaveCount(1);
});

it('names the faulty item by position', function (array $items, string $expected, string $menu = 'footer'): void {
    expect(($this->reason)(fn () => ($this->save)($menu, $items)))->toBe($expected);
})->with([
    'empty label' => [[new Item(' ', 'issues')], 'menu_item_label@1'],
    'unknown type' => [[new Item('س', 'issues'), new Item('ص', 'video')], 'menu_item_type@2'],
    'missing category' => [[new Item('س', 'category', 7)], 'menu_item_target@1'],
    'missing page in child' => [[new Item('س', 'issues', children: [new Item('ص', 'page', 11)])], 'menu_item_target@1.1'],
    'javascript url' => [[new Item('س', 'url', url: 'javascript:alert(1)')], 'menu_item_url@1'],
    'protocol-relative url' => [[new Item('س', 'url', url: '//evil.example')], 'menu_item_url@1'],
    'two levels' => [[new Item('س', 'issues', children: [new Item('ص', 'issues', children: [new Item('ع', 'issues')])])], 'menu_too_deep@1.1'],
    'bad icon' => [[new Item('س', 'issues', icon: '<svg>')], 'invalid_icon@1'],
    'too many' => [array_fill(0, 41, new Item('س', 'issues')), 'menu_too_long'],
    'unknown menu' => [[], 'unknown_menu', 'sidebar'],
]);

it('needs the menus permission', function (): void {
    $this->authorizer->revoke(LayoutPermission::ManageMenus->value);

    expect(fn () => ($this->save)('header', []))->toThrow(NotAuthorized::class);
});
