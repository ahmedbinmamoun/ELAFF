<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article;

interface EditorialSettings
{
    /**
     * FR-CON-08 `contributors.editor_can_publish` (default false): editors may
     * publish or schedule articles written by contributors.
     */
    public function editorsCanPublishContributorArticles(): bool;
}
