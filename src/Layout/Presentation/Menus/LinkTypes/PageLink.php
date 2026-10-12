<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Menus\LinkTypes;

use Elaf\Layout\Application\Menu\Query\MenuEntry;
use Elaf\Layout\Domain\Menu\MenuItemType;
use Elaf\Layout\Presentation\Menus\MenuLinkType;
use Illuminate\Http\Request;

final class PageLink implements MenuLinkType
{
    public function type(): string
    {
        return MenuItemType::Page->value;
    }

    public function url(MenuEntry $entry): string
    {
        return route('page.show', (string) $entry->slug);
    }

    public function isActive(MenuEntry $entry, Request $request): bool
    {
        return $request->routeIs('page.show') && $request->route('slug') === $entry->slug;
    }
}
