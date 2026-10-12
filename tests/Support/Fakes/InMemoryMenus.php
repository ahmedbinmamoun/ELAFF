<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Layout\Application\Menu\MenuRepository;
use Elaf\Layout\Application\Menu\MenuTargets;
use Elaf\Layout\Domain\Menu\MenuKey;

/**
 * Menus and the records they may point at.
 */
final class InMemoryMenus implements MenuRepository, MenuTargets
{
    /** @var array<string, list<\Elaf\Layout\Application\Menu\MenuItemData>> */
    private array $menus = [];

    /** @var list<int> */
    public array $categories = [];

    /** @var list<int> */
    public array $pages = [];

    public function items(MenuKey $menu): array
    {
        return $this->menus[$menu->value] ?? [];
    }

    public function replace(MenuKey $menu, array $items): void
    {
        $this->menus[$menu->value] = $items;
    }

    public function categoryExists(int $id): bool
    {
        return in_array($id, $this->categories, true);
    }

    public function pageExists(int $id): bool
    {
        return in_array($id, $this->pages, true);
    }
}
