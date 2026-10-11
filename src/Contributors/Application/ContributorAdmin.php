<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application;

use Elaf\Contributors\Domain\Contributor;
use Elaf\Contributors\Domain\ContributorRepository;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;

/**
 * Load → change → save → dispatch for editor actions on a contributor,
 * behind the contributors.manage permission.
 */
final readonly class ContributorAdmin
{
    public function __construct(
        private ContributorRepository $contributors,
        private Authorizer $authorizer,
        private Transaction $transaction,
        private EventDispatcher $events,
    ) {}

    /**
     * @param  callable(Contributor): void  $change  runs inside the transaction
     */
    public function change(int $contributorId, callable $change): void
    {
        if (! $this->authorizer->allows(ContributorPermission::ManageContributors->value)) {
            throw NotAuthorized::lacks(ContributorPermission::ManageContributors->value);
        }

        $this->transaction->run(function () use ($contributorId, $change): void {
            $contributor = $this->contributors->get(UserId::fromInt($contributorId));
            $change($contributor);
            $this->contributors->save($contributor);
            $this->events->dispatch(...$contributor->releaseEvents());
        });
    }
}
