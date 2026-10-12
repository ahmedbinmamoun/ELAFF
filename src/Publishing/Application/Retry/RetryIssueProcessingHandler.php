<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Retry;

use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Application\Issue\IssueAccess;
use Elaf\Publishing\Application\Issue\IssueUnitOfWork;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-ISS-06: a failed issue goes back to pending and is queued again.
 */
final readonly class RetryIssueProcessingHandler
{
    public function __construct(
        private IssueAccess $access,
        private IssueUnitOfWork $issues,
        private Clock $clock,
    ) {}

    public function handle(RetryIssueProcessingCommand $command): void
    {
        $this->access->requireManage();
        $issue = $this->issues->load(IssueId::fromInt($command->issueId));

        $issue->retry(UserId::fromInt($command->actorId), $this->clock);
        $this->issues->persist($issue);
    }
}
