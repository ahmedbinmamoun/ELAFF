<?php

declare(strict_types=1);

use Elaf\Shared\Domain\Exception\InvalidIdentifier;
use Tests\Support\Doubles\OtherId;
use Tests\Support\Doubles\SampleId;

it('holds a positive integer', function (): void {
    $id = SampleId::fromInt(42);

    expect($id->value)->toBe(42)
        ->and((string) $id)->toBe('42');
});

it('rejects zero and negative values', function (int $value): void {
    SampleId::fromInt($value);
})->with([0, -1])->throws(InvalidIdentifier::class);

it('is equal only to the same type and value', function (): void {
    expect(SampleId::fromInt(1)->equals(SampleId::fromInt(1)))->toBeTrue()
        ->and(SampleId::fromInt(1)->equals(SampleId::fromInt(2)))->toBeFalse()
        ->and(SampleId::fromInt(1)->equals(OtherId::fromInt(1)))->toBeFalse();
});
