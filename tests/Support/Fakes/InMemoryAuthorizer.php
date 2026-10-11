<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Shared\Application\Authorization\Authorizer;

final class InMemoryAuthorizer implements Authorizer
{
    /** @var array<string, true> */
    private array $granted = [];

    /**
     * @param  list<string>  $abilities
     */
    public function __construct(array $abilities = [])
    {
        $this->grant(...$abilities);
    }

    public function grant(string ...$abilities): void
    {
        foreach ($abilities as $ability) {
            $this->granted[$ability] = true;
        }
    }

    public function revoke(string $ability): void
    {
        unset($this->granted[$ability]);
    }

    public function allows(string $ability, ?object $subject = null): bool
    {
        return isset($this->granted[$ability]);
    }
}
