<?php

declare(strict_types=1);

namespace Elaf\Publishing\Infrastructure\Processing;

use Elaf\Publishing\Domain\Issue\Events\IssueProcessingRetried;
use Elaf\Publishing\Domain\Issue\Events\IssueUploaded;
use Illuminate\Contracts\Bus\Dispatcher;

/**
 * IssueUploaded / IssueProcessingRetried → queue the rendering (PRD §11).
 */
final readonly class QueueIssueProcessing
{
    public function __construct(private Dispatcher $bus) {}

    public function handle(IssueUploaded|IssueProcessingRetried $event): void
    {
        $this->bus->dispatch(new ProcessIssuePdfJob($event->issueId->value));
    }
}
