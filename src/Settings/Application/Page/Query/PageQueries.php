<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page\Query;

interface PageQueries
{
    /**
     * A published page by slug; null when missing or hidden.
     */
    public function published(string $slug): ?PageView;
}
