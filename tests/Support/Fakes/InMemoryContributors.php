<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Contributors\Application\ContributorAccounts;
use Elaf\Contributors\Domain\Contributor;
use Elaf\Contributors\Domain\ContributorRepository;
use Elaf\Contributors\Domain\DisplayName;
use Elaf\Contributors\Domain\Exception\ContributorNotFound;
use Elaf\Identity\Domain\UserId;

/**
 * Accounts and contributor records together, as the users table holds both.
 */
final class InMemoryContributors implements ContributorAccounts, ContributorRepository
{
    /** @var array<int, array{name: string, email: string, hash: string}> */
    public array $accounts = [];

    /** @var array<int, Contributor> */
    private array $contributors = [];

    private int $lastId = 100;

    public function create(DisplayName $name, string $email, string $passwordHash): UserId
    {
        $id = ++$this->lastId;
        $this->accounts[$id] = ['name' => $name->value, 'email' => mb_strtolower($email), 'hash' => $passwordHash];

        return UserId::fromInt($id);
    }

    public function emailExists(string $email): bool
    {
        foreach ($this->accounts as $account) {
            if ($account['email'] === mb_strtolower($email)) {
                return true;
            }
        }

        return false;
    }

    public function get(UserId $id): Contributor
    {
        if (! isset($this->contributors[$id->value])) {
            throw ContributorNotFound::withId($id);
        }

        return clone $this->contributors[$id->value];
    }

    public function save(Contributor $contributor): void
    {
        $copy = clone $contributor;
        $copy->releaseEvents();
        $this->contributors[$contributor->id()->value] = $copy;
    }
}
