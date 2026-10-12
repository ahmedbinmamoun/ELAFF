<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Menus;

/**
 * A menu item ready for a view.
 */
final readonly class MenuLink
{
    /**
     * @param  list<MenuLink>  $children
     */
    public function __construct(
        public string $label,
        public string $url,
        public ?string $icon,
        public bool $active,
        public bool $external,
        public array $children = [],
    ) {}
}
