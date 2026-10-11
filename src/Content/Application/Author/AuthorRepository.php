<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Author;

use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Text\Slug;

interface AuthorRepository
{
    public function create(AuthorData $data): AuthorId;

    public function update(AuthorId $id, AuthorData $data): void;

    public function find(AuthorId $id): ?Author;

    public function findByUser(UserId $userId): ?Author;

    public function slugExists(Slug $slug, ?AuthorId $except = null): bool;

    public function hasArticles(AuthorId $id): bool;

    public function delete(AuthorId $id): void;
}
