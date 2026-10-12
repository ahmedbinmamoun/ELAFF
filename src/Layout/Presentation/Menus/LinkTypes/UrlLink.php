<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Menus\LinkTypes;

use Elaf\Layout\Application\Menu\Query\MenuEntry;
use Elaf\Layout\Domain\Menu\MenuItemType;
use Elaf\Layout\Presentation\Menus\MenuLinkType;
use Illuminate\Http\Request;

/**
 * Internal paths ("/letters") or external http(s) links.
 */
final class UrlLink implements MenuLinkType
{
    public function type(): string
    {
        return MenuItemType::Url->value;
    }

    public function url(MenuEntry $entry): string
    {
        $url = (string) $entry->url;

        return str_starts_with($url, '/') ? url($url) : $url;
    }

    public function isActive(MenuEntry $entry, Request $request): bool
    {
        $url = (string) $entry->url;

        return str_starts_with($url, '/') && '/'.ltrim($request->path(), '/') === rtrim($url, '/');
    }
}
