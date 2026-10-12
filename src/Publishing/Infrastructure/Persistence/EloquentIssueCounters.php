<?php

declare(strict_types=1);

namespace Elaf\Publishing\Infrastructure\Persistence;

use Elaf\Publishing\Application\Port\IssueArticleLinks;
use Elaf\Publishing\Application\Port\IssueDownloads;
use Elaf\Publishing\Domain\Issue\IssueId;
use Illuminate\Database\ConnectionInterface;

/**
 * Plain counters around issues, outside the aggregate.
 */
final readonly class EloquentIssueCounters implements IssueArticleLinks, IssueDownloads
{
    public function __construct(private ConnectionInterface $db) {}

    public function count(IssueId $id): int
    {
        return $this->db->table('articles')->where('issue_id', $id->value)->count();
    }

    /**
     * One atomic UPDATE; no read-modify-write race.
     */
    public function record(IssueId $id): void
    {
        $this->db->table('issues')->where('id', $id->value)->increment('downloads_count');
    }
}
