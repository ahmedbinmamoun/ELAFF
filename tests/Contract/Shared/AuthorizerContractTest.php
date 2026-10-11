<?php

declare(strict_types=1);

use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Infrastructure\Authorization\GateAuthorizer;
use Illuminate\Support\Facades\Gate;
use Tests\Support\Fakes\InMemoryAuthorizer;

dataset('authorizers', [
    'gate' => function (): Authorizer {
        Gate::before(static fn (mixed $user, string $ability): ?bool => $ability === 'contract.granted' ? true : null);

        return app(GateAuthorizer::class);
    },
    'in-memory' => fn (): Authorizer => new InMemoryAuthorizer(['contract.granted']),
]);

it('allows a granted ability and denies an unknown one', function (Authorizer $authorizer): void {
    expect($authorizer->allows('contract.granted'))->toBeTrue()
        ->and($authorizer->allows('contract.unknown'))->toBeFalse();
})->with('authorizers');
