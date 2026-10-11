<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Identity\Application\StaffUser\StaffUser;
use Elaf\Identity\Application\StaffUser\StaffUserData;
use Elaf\Identity\Application\StaffUser\StaffUserRepository;
use Elaf\Identity\Domain\UserId;

final class InMemoryStaffUserRepository implements StaffUserRepository
{
    /** @var array<int, StaffUserData> */
    private array $rows = [];

    /** @var array<int, string> */
    public array $passwordHashes = [];

    /** @var array<int, string> emails of non-staff accounts (e.g. contributors) */
    private array $otherEmails = [];

    private int $lastId = 0;

    public function create(StaffUserData $data, string $passwordHash): UserId
    {
        $id = ++$this->lastId;
        $this->rows[$id] = $data;
        $this->passwordHashes[$id] = $passwordHash;

        return UserId::fromInt($id);
    }

    public function update(UserId $id, StaffUserData $data): void
    {
        $this->rows[$id->value] = $data;
    }

    public function changePassword(UserId $id, string $passwordHash): void
    {
        $this->passwordHashes[$id->value] = $passwordHash;
    }

    public function find(UserId $id): ?StaffUser
    {
        return isset($this->rows[$id->value]) ? new StaffUser($id, $this->rows[$id->value]) : null;
    }

    public function emailExists(string $email, ?UserId $except = null): bool
    {
        $email = mb_strtolower($email);

        foreach ($this->rows as $id => $data) {
            if ($data->email === $email && $id !== $except?->value) {
                return true;
            }
        }

        return in_array($email, $this->otherEmails, true);
    }

    /**
     * Test setup: an account that exists but holds no staff role.
     */
    public function addNonStaffAccount(string $email): UserId
    {
        $this->otherEmails[++$this->lastId] = mb_strtolower($email);

        return UserId::fromInt($this->lastId);
    }
}
