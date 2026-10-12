<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage;

interface HomepageBlockQueries
{
    /**
     * The active blocks in order; null when none were ever stored (a fresh
     * installation shows the default composition).
     *
     * @return list<HomepageBlockData>|null
     */
    public function active(): ?array;
}
