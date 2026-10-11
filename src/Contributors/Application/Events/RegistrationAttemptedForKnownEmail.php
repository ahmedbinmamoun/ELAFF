<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\Events;

use DateTimeImmutable;
use Elaf\Shared\Domain\Event\DomainEvent;

/**
 * Someone registered with an email that already has an account. The form
 * answers as for a new registration (no enumeration, FR-CON-01); the owner
 * of the address is told by email instead.
 */
final readonly class RegistrationAttemptedForKnownEmail implements DomainEvent
{
    public function __construct(
        public string $email,
        public DateTimeImmutable $at,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->at;
    }
}
