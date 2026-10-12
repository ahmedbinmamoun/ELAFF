<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Menus;

use Elaf\Layout\Application\Menu\Query\MenuEntry;
use Illuminate\Http\Request;
use InvalidArgumentException;

final class MenuLinkRegistry
{
    /** @var array<string, MenuLinkType> */
    private array $types = [];

    /**
     * @param  iterable<MenuLinkType>  $types
     */
    public function __construct(iterable $types)
    {
        foreach ($types as $type) {
            $this->types[$type->type()] = $type;
        }
    }

    public function link(MenuEntry $entry, Request $request): MenuLink
    {
        $type = $this->types[$entry->type->value] ?? throw new InvalidArgumentException("No link type for {$entry->type->value}.");
        $url = $type->url($entry);

        return new MenuLink(
            $entry->label,
            $url,
            $entry->icon,
            $type->isActive($entry, $request),
            ! str_starts_with($url, url('/')),
            array_map(fn (MenuEntry $child): MenuLink => $this->link($child, $request), $entry->children),
        );
    }
}
