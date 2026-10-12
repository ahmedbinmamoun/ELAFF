<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Menu;

use Elaf\Layout\Domain\Menu\MenuKey;

interface MenuRepository
{
    /**
     * @return list<MenuItemData> all items (hidden ones too), in order
     */
    public function items(MenuKey $menu): array;

    /**
     * Replaces the whole menu in one go.
     *
     * @param  list<MenuItemData>  $items
     */
    public function replace(MenuKey $menu, array $items): void;
}
