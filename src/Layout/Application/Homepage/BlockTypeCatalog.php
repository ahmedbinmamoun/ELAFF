<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Homepage;

/**
 * The block types the site knows (FR-HOME-02). Implemented by the
 * presentation's renderer registry: a type exists when a renderer does.
 */
interface BlockTypeCatalog
{
    public function knows(string $type): bool;
}
