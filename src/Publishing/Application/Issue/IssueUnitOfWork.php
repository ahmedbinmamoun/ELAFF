<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Issue;

use Elaf\Publishing\Domain\Issue\Issue;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Domain\Issue\IssueRepository;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;

/**
 * Load → change → save → dispatch for issue use cases.
 */
final readonly class IssueUnitOfWork
{
    public function __construct(
        private IssueRepository $issues,
        private Transaction $transaction,
        private EventDispatcher $events,
    ) {}

    public function load(IssueId $id): Issue
    {
        return $this->issues->get($id);
    }

    public function nextId(): IssueId
    {
        return $this->issues->nextId();
    }

    public function persist(Issue $issue): void
    {
        $this->transaction->run(function () use ($issue): void {
            $this->issues->save($issue);
            $this->events->dispatch(...$issue->releaseEvents());
        });
    }
}
