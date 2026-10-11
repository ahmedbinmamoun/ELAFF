<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Article\SubmitArticleForReview;

use Elaf\Content\Application\Article\ArticleAccess;
use Elaf\Content\Application\Article\ArticleCounts;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ART-04: draft or returned → in_review. A contributor may have at most
 * MAX_IN_REVIEW articles waiting at once (FR-CON-10).
 */
final readonly class SubmitArticleForReviewHandler
{
    public const MAX_IN_REVIEW = 5;

    public function __construct(
        private ArticleUnitOfWork $articles,
        private ArticleAccess $access,
        private ArticleCounts $counts,
        private Clock $clock,
    ) {}

    public function handle(SubmitArticleForReviewCommand $command): void
    {
        $actor = UserId::fromInt($command->actorId);
        $article = $this->articles->load(ArticleId::fromInt($command->articleId));

        $this->access->requireOwnerOrEditor($article, $actor);

        if ($article->origin() === ArticleOrigin::Contributor && $this->counts->inReviewBy($article->createdBy()) >= self::MAX_IN_REVIEW) {
            throw InvalidInput::because('article', 'too_many_in_review', 'Too many articles are already waiting for review.', ['max' => self::MAX_IN_REVIEW]);
        }

        $article->submitForReview($actor, $this->clock);
        $this->articles->persist($article);
    }
}
