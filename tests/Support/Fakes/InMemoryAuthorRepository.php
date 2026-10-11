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

    public function create(AuthorData $data): AuthorId
    {
        $id = count($this->rows) + 1;
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
}
