<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Menus;

use Elaf\Layout\Application\Menu\Query\MenuEntry;
use Illuminate\Http\Request;

/**
 * Turns one kind of menu item into a link (FR-NAV-01). One class per type,
 * registered in LayoutPresentationServiceProvider.
 */
interface MenuLinkType
{
    public function type(): string;

    public function url(MenuEntry $entry): string;

    public function isActive(MenuEntry $entry, Request $request): bool;
}
