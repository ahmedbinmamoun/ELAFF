<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\Revision;

use DateTimeImmutable;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;

final readonly class ArticleRevision
{
    public function __construct(
        public int $id,
        public ArticleId $articleId,
        public UserId $userId,
        public DateTimeImmutable $createdAt,
        public ArticleContent $content,
    ) {}
}
