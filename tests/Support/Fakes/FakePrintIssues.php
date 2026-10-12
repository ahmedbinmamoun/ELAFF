<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Content\Application\Article\PrintIssue;
use Elaf\Content\Application\Article\PrintIssues;
use Elaf\Publishing\Domain\Issue\IssueId;

final class FakePrintIssues implements PrintIssues
{
    /** @var array<int, PrintIssue> */
    public array $issues = [];

    public function add(int $id, int $number, ?int $pageCount): void
    {
        $this->issues[$id] = new PrintIssue($number, $pageCount);
    }

    public function find(IssueId $id): ?PrintIssue
    {
        return $this->issues[$id->value] ?? null;
    }
}
