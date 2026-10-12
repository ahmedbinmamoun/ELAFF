<?php

declare(strict_types=1);

namespace Elaf\Engagement\Application\Letter\Convert;

use Elaf\Content\Domain\Article\ArticleId;

/**
 * Creates the article draft for a letter in the Content module, in the
 * "بريد القراء" section, as the moderator. Only the sender's name crosses.
 */
interface LetterArticles
{
    public function createDraft(LetterDraft $draft, int $actorId): ArticleId;
}
