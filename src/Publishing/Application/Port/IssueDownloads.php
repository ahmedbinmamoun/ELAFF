<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Port;

use Elaf\Publishing\Domain\Issue\IssueId;

/**
 * PDF download counter (PRD §9). A read-model counter, not aggregate state.
 */
interface IssueDownloads
{
    public function record(IssueId $id): void;
}
