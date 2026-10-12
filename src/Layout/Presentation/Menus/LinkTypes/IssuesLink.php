<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Menus\LinkTypes;

use Elaf\Layout\Application\Menu\Query\MenuEntry;
use Elaf\Layout\Domain\Menu\MenuItemType;
use Elaf\Layout\Presentation\Menus\MenuLinkType;
use Illuminate\Http\Request;

final class IssuesLink implements MenuLinkType
{
    public function type(): string
    {
        return MenuItemType::Issues->value;
    }

    public function url(MenuEntry $entry): string
    {
        return route('issues.index');
    }

    public function isActive(MenuEntry $entry, Request $request): bool
    {
        return $request->routeIs('issues.*');
    }
}
