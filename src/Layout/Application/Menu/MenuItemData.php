<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Menu;

use Elaf\Layout\Domain\Menu\MenuItemType;

/**
 * A validated menu item, children in order.
 */
final readonly class MenuItemData
{
    /**
     * @param  list<MenuItemData>  $children
     */
    public function __construct(
        public string $label,
        public MenuItemType $type,
        public ?int $targetId,
        public ?string $url,
        public ?string $icon,
        public bool $isVisible,
        public array $children = [],
    ) {}
}
