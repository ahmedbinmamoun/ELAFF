<?php

declare(strict_types=1);

namespace Elaf\Contributors\Domain;

/**
 * Account lifecycle of an external writer (FR-CON-02, 03, 12).
 */
enum ContributorStatus: string
{
    case PendingEmail = 'pending_email';
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingEmail => [self::PendingApproval],
            self::PendingApproval => [self::Active, self::Rejected],
            self::Active => [self::Suspended],
            self::Suspended => [self::Active],
            self::Rejected => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * Pending contributors may sign in, but only to see their status.
     */
    public function canSignIn(): bool
    {
        return $this === self::PendingApproval || $this === self::Active;
    }

    public function canWrite(): bool
    {
        return $this === self::Active;
    }
}
