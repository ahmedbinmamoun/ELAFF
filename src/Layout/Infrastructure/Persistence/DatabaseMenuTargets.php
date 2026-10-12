<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure\Persistence;

use Elaf\Layout\Application\Menu\MenuTargets;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabaseMenuTargets implements MenuTargets
{
    public function __construct(private ConnectionInterface $db) {}

    public function categoryExists(int $id): bool
    {
        return $this->db->table('categories')->where('id', $id)->exists();
    }

    public function pageExists(int $id): bool
    {
        return $this->db->table('pages')->where('id', $id)->exists();
    }
}
