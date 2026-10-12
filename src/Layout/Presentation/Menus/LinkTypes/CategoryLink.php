<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Menus\LinkTypes;

use Elaf\Layout\Application\Menu\Query\MenuEntry;
use Elaf\Layout\Domain\Menu\MenuItemType;
use Elaf\Layout\Presentation\Menus\MenuLinkType;
use Illuminate\Http\Request;

final class CategoryLink implements MenuLinkType
{
    public function type(): string
    {
        return MenuItemType::Category->value;
    }

    public function url(MenuEntry $entry): string
    {
        return route('category.show', (string) $entry->slug);
    }

    public function isActive(MenuEntry $entry, Request $request): bool
    {
        return $request->routeIs('category.show') && $request->route('slug') === $entry->slug;
    }
}
