<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Download;

use Elaf\Publishing\Application\Port\IssueDownloads;
use Elaf\Publishing\Domain\Issue\IssueId;

/**
 * PRD §9: /issues/{number}/pdf counts downloads.
 */
final readonly class RecordIssueDownloadHandler
{
    public function __construct(private IssueDownloads $downloads) {}

    public function handle(int $issueId): void
    {
        $this->downloads->record(IssueId::fromInt($issueId));
    }
}
