<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Menu\Query;

interface MenuQueries
{
    /**
     * Every menu's visible items whose target is public (visible category,
     * published page), in order, in one query.
     *
     * @return array<string, list<MenuEntry>> by MenuKey value; missing when empty
     */
    public function all(): array;
}
