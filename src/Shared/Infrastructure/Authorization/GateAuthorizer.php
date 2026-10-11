<?php

declare(strict_types=1);

namespace Elaf\Shared\Infrastructure\Authorization;

use Elaf\Shared\Application\Authorization\Authorizer;
use Illuminate\Contracts\Auth\Access\Gate;

final readonly class GateAuthorizer implements Authorizer
{
    public function __construct(private Gate $gate) {}

    public function allows(string $ability, ?object $subject = null): bool
    {
        return $this->gate->allows($ability, $subject === null ? [] : [$subject]);
    }
}
