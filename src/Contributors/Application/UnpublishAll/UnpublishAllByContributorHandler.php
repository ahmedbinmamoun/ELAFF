<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\UnpublishAll;

use Elaf\Content\Application\Article\ArticleCounts;
use Elaf\Content\Application\Article\ArticleUnitOfWork;
use Elaf\Contributors\Application\ContributorPermission;
use Elaf\Contributors\Domain\ContributorRepository;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-CON-12 bulk action: takes every published article of a contributor
 * offline. Each article goes through its own unpublish (events, audit log).
 */
final readonly class UnpublishAllByContributorHandler
{
    public function __construct(
        private ContributorRepository $contributors,
        private ArticleCounts $counts,
        private ArticleUnitOfWork $articles,
        private Authorizer $authorizer,
        private Clock $clock,
    ) {}

    /**
     * @return int how many articles were unpublished
     */
    public function handle(UnpublishAllByContributorCommand $command): int
    {
        if (! $this->authorizer->allows(ContributorPermission::ManageContributors->value)) {
            throw NotAuthorized::lacks(ContributorPermission::ManageContributors->value);
        }

        $contributor = $this->contributors->get(UserId::fromInt($command->contributorId));
        $actor = UserId::fromInt($command->actorId);
        $ids = $this->counts->publishedBy($contributor->id());

        foreach ($ids as $id) {
            $article = $this->articles->load($id);
            $article->unpublish($actor, $this->clock);
            $this->articles->persist($article);
        }

        return count($ids);
    }
}
