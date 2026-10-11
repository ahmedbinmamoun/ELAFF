<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\CreateArticleDraft;

use Elaf\Content\Application\Article\ArticleInput;
use Elaf\Content\Domain\Article\ArticleOrigin;

final readonly class CreateArticleDraftCommand
{
    /**
     * @param  ArticleOrigin  $origin  set by the panel the request came from (admin or contributor portal)
     */
    public function __construct(
        public int $actorId,
        public ArticleOrigin $origin,
        public ArticleInput $article,
    ) {}
}
