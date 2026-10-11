<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Content\Application\Author\Author;
use Elaf\Content\Application\Author\AuthorData;
use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Text\Slug;
use RuntimeException;

final class InMemoryAuthorRepository implements AuthorRepository
{
    /** @var array<int, AuthorData> */
    private array $rows = [];

    private int $lastId = 0;

    public function create(AuthorData $data): AuthorId
    {
        $id = ++$this->lastId;
        $this->rows[$id] = $data;

        return AuthorId::fromInt($id);
    }

    public function update(AuthorId $id, AuthorData $data): void
    {
        if (! isset($this->rows[$id->value])) {
            throw new RuntimeException("Author {$id} does not exist.");
        }

        $this->rows[$id->value] = $data;
    }

    public function find(AuthorId $id): ?Author
    {
        return isset($this->rows[$id->value]) ? new Author($id, $this->rows[$id->value]) : null;
    }

    public function findByUser(UserId $userId): ?Author
    {
        foreach ($this->rows as $id => $data) {
            if ($data->userId?->equals($userId) === true) {
                return new Author(AuthorId::fromInt($id), $data);
            }
        }

        return null;
    }

    public function slugExists(Slug $slug, ?AuthorId $except = null): bool
    {
        foreach ($this->rows as $id => $data) {
            if ($data->slug->equals($slug) && $id !== $except?->value) {
                return true;
            }
        }

        return false;
    }

    /** @var array<int, true> */
    private array $usedByArticles = [];

    /**
     * Test setup: pretend an article references this record.
     */
    public function markUsedByArticle(AuthorId $id): void
    {
        $this->usedByArticles[$id->value] = true;
    }

    public function hasArticles(AuthorId $id): bool
    {
        return isset($this->usedByArticles[$id->value]);
    }

    public function delete(AuthorId $id): void
    {
        unset($this->rows[$id->value]);
    }
}
