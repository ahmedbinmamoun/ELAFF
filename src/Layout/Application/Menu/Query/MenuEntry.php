<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Menu\Query;

use Elaf\Layout\Domain\Menu\MenuItemType;

/**
 * A visible menu item for the public site. $slug is the target's slug for
 * category and page items; $icon falls back to the category's icon.
 */
final readonly class MenuEntry
{
    /**
     * @param  list<MenuEntry>  $children
     */
    public function __construct(
        public string $label,
        public MenuItemType $type,
        public ?string $slug,
        public ?string $url,
        public ?string $icon,
        public array $children = [],
    ) {}
}
