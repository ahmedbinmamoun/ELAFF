<?php

declare(strict_types=1);

use Elaf\Shared\Domain\Time\Clock;
use Elaf\Shared\Infrastructure\Time\SystemClock;
use Tests\Support\Fakes\FrozenClock;

dataset('clocks', [
    'system' => fn (): Clock => new SystemClock(new DateTimeZone('Africa/Khartoum')),
    'frozen' => fn (): Clock => new FrozenClock,
    'container' => fn (): Clock => app(Clock::class),
]);

it('returns the current moment in the newsroom timezone', function (Clock $clock): void {
    expect($clock->now()->getTimezone()->getName())->toBe('Africa/Khartoum');
})->with('clocks');

it('never goes backwards between two reads', function (Clock $clock): void {
    $first = $clock->now();

    expect($clock->now() >= $first)->toBeTrue();
})->with('clocks');
