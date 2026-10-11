<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\WithdrawSubmission;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-CON-06: in_review → draft, back to the writer.
 */
final readonly class WithdrawSubmissionHandler
{
    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleAccess $access,
        private Clock $clock,
    ) {}

    public function handle(WithdrawSubmissionCommand $command): void
    {
        $actor = UserId::fromInt($command->actorId);
        $article = $this->articles->load(ArticleId::fromInt($command->articleId));

        $this->access->requireOwnerOrEditor($article, $actor);

        $article->withdraw($actor, $this->clock);
        $this->articles->persist($article);
    }
}
