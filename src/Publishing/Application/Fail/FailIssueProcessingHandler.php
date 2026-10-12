<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Fail;

use Elaf\Publishing\Application\Issue\IssueUnitOfWork;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Domain\Issue\ProcessingStatus;
use Elaf\Shared\Domain\Time\Clock;

/**
 * The processing job died (timeout, out of memory, worker killed) before it
 * could record the outcome: the issue must not stay "processing" forever.
 */
final readonly class FailIssueProcessingHandler
{
    public function __construct(
        private IssueUnitOfWork $issues,
        private Clock $clock,
    ) {}

    public function handle(FailIssueProcessingCommand $command): void
    {
        $issue = $this->issues->load(IssueId::fromInt($command->issueId));

        if (! in_array($issue->status(), [ProcessingStatus::Pending, ProcessingStatus::Processing], true)) {
            return;
        }

        $issue->markFailed($command->reason, $this->clock);
        $this->issues->persist($issue);
    }
}
