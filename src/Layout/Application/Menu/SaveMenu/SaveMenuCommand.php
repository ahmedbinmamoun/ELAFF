<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Menu\SaveMenu;

use Elaf\Layout\Application\Menu\MenuItemInput;

final readonly class SaveMenuCommand
{
    /**
     * @param  list<MenuItemInput>  $items  in display order
     */
    public function __construct(
        public int $actorId,
        public string $menu,
        public array $items,
    ) {}
}
