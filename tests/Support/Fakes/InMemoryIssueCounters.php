<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Publishing\Application\Port\IssueArticleLinks;
use Elaf\Publishing\Application\Port\IssueDownloads;
use Elaf\Publishing\Domain\Issue\IssueId;

final class InMemoryIssueCounters implements IssueArticleLinks, IssueDownloads
{
    /** @var array<int, int> */
    public array $links = [];

    /** @var array<int, int> */
    public array $downloads = [];

    public function count(IssueId $id): int
    {
        return $this->links[$id->value] ?? 0;
    }

    public function record(IssueId $id): void
    {
        $this->downloads[$id->value] = ($this->downloads[$id->value] ?? 0) + 1;
    }
}
