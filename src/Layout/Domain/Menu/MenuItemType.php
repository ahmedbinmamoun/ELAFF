<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Menu;

/**
 * What a menu item links to (FR-NAV-01): a closed set.
 */
enum MenuItemType: string
{
    case Category = 'category';
    case Page = 'page';
    case Issues = 'issues';
    case Url = 'url';

    public function needsTarget(): bool
    {
        return $this === self::Category || $this === self::Page;
    }
}
