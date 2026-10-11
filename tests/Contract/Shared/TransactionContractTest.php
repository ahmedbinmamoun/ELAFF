<?php

declare(strict_types=1);

use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Infrastructure\Transaction\DbTransaction;
use Tests\Support\Fakes\ImmediateTransaction;

dataset('transactions', [
    'database' => fn (): Transaction => app(DbTransaction::class),
    'immediate' => fn (): Transaction => new ImmediateTransaction,
]);

it('returns the result of the work', function (Transaction $transaction): void {
    expect($transaction->run(static fn (): string => 'done'))->toBe('done');
})->with('transactions');

it('rethrows the exception thrown by the work unchanged', function (Transaction $transaction): void {
    $original = new RuntimeException('boom');

    try {
        $transaction->run(static function () use ($original): never {
            throw $original;
        });
        $this->fail('Expected exception was not rethrown.');
    } catch (RuntimeException $caught) {
        expect($caught)->toBe($original);
    }
})->with('transactions');
