<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Shared\Application\Transaction\Transaction;

final class ImmediateTransaction implements Transaction
{
    public int $runs = 0;

    public function run(callable $work): mixed
    {
        $this->runs++;

        return $work();
    }
}
