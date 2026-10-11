<?php

declare(strict_types=1);

namespace Elaf\Shared\Application\Transaction;

interface Transaction
{
    /**
     * Runs $work atomically and returns its result. Any exception thrown by
     * $work rolls the transaction back and is rethrown unchanged.
     *
     * @template T
     *
     * @param  callable(): T  $work
     * @return T
     */
    public function run(callable $work): mixed;
}
