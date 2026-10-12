<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Delete;

use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Application\Issue\IssueAccess;
use Elaf\Publishing\Application\Issue\IssuePdfStore;
use Elaf\Publishing\Application\Port\IssueArticleLinks;
use Elaf\Publishing\Domain\Issue\Events\IssueDeleted;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Domain\Issue\IssueRepository;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Domain\Time\Clock;

/**
 * Removes a wrongly uploaded issue and its files. Refused while articles
 * point at it: their print links would break silently.
 */
final readonly class DeleteIssueHandler
{
    public function __construct(
        private IssueAccess $access,
        private IssueRepository $issues,
        private IssueArticleLinks $links,
        private IssuePdfStore $pdfs,
        private Transaction $transaction,
        private EventDispatcher $events,
        private Clock $clock,
    ) {}

    public function handle(DeleteIssueCommand $command): void
    {
        $this->access->requireManage();
        $id = IssueId::fromInt($command->issueId);
        $issue = $this->issues->get($id);
        $linked = $this->links->count($id);

        if ($linked > 0) {
            throw InvalidInput::because('issue', 'issue_has_articles', sprintf('%d articles are linked to this issue.', $linked), ['count' => $linked]);
        }

        $this->transaction->run(function () use ($id, $command): void {
            $this->issues->delete($id);
            $this->events->dispatch(new IssueDeleted($id, UserId::fromInt($command->actorId), $this->clock->now()));
        });

        $this->pdfs->discard($issue->file());
    }
}
