<?php

declare(strict_types=1);

namespace Elaf\Publishing\Infrastructure\Processing;

use Elaf\Publishing\Application\Fail\FailIssueProcessingCommand;
use Elaf\Publishing\Application\Fail\FailIssueProcessingHandler;
use Elaf\Publishing\Application\Process\ProcessIssuePdfCommand;
use Elaf\Publishing\Application\Process\ProcessIssuePdfHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Thin queue wrapper around ProcessIssuePdf (PRD §11 step 3). One attempt:
 * a failure is recorded on the issue and an editor retries it from the admin.
 */
final class ProcessIssuePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    /** Below the worker's --timeout=300 so failed() still runs. */
    public int $timeout = 290;

    public function __construct(public readonly int $issueId) {}

    public function handle(ProcessIssuePdfHandler $handler): void
    {
        $handler->handle(new ProcessIssuePdfCommand($this->issueId));
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception !== null) {
            report($exception);
        }

        app(FailIssueProcessingHandler::class)->handle(new FailIssueProcessingCommand($this->issueId, 'job_crashed'));
    }
}
