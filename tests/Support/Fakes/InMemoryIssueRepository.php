<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Publishing\Domain\Issue\Exception\IssueNotFound;
use Elaf\Publishing\Domain\Issue\Issue;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Domain\Issue\IssueNumber;
use Elaf\Publishing\Domain\Issue\IssueRepository;

final class InMemoryIssueRepository implements IssueRepository
{
    /** @var array<int, Issue> */
    private array $issues = [];

    private int $lastId = 0;

    public function nextId(): IssueId
    {
        return IssueId::fromInt(++$this->lastId);
    }

    public function get(IssueId $id): Issue
    {
        return $this->issues[$id->value] ?? throw IssueNotFound::withId($id);
    }

    public function findByNumber(IssueNumber $number): ?Issue
    {
        foreach ($this->issues as $issue) {
            if ($issue->number()->equals($number)) {
                return $issue;
            }
        }

        return null;
    }

    /**
     * Stores a copy (later changes to the object are not saved by accident)
     * without pending events, like a reconstituted issue.
     */
    public function save(Issue $issue): void
    {
        $copy = clone $issue;
        $copy->releaseEvents();
        $this->issues[$issue->id()->value] = $copy;
    }

    public function delete(IssueId $id): void
    {
        unset($this->issues[$id->value]);
    }

    public function count(): int
    {
        return count($this->issues);
    }
}
