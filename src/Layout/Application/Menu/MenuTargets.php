<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Menu;

/**
 * Whether the records a menu item points at exist.
 */
interface MenuTargets
{
    public function categoryExists(int $id): bool;

    public function pageExists(int $id): bool;
}
