<?php

declare(strict_types=1);

namespace Elaf\Shared\Infrastructure\Transaction;

use Elaf\Shared\Application\Transaction\Transaction;
use Illuminate\Database\ConnectionInterface;

final readonly class DbTransaction implements Transaction
{
    public function __construct(private ConnectionInterface $connection) {}

    public function run(callable $work): mixed
    {
        return $this->connection->transaction(static fn () => $work());
    }
}
