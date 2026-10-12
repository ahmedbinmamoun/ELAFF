<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue;

use Elaf\Publishing\Domain\Issue\Exception\IssueNotFound;

/**
 * Write side only. Reads go through IssueQueries.
 */
interface IssueRepository
{
    public function nextId(): IssueId;

    /**
     * @throws IssueNotFound
     */
    public function get(IssueId $id): Issue;

    public function findByNumber(IssueNumber $number): ?Issue;

    public function save(Issue $issue): void;

    public function delete(IssueId $id): void;
}
