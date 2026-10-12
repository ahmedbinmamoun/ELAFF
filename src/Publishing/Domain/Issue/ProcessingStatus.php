<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue;

/**
 * PDF processing state (FR-ISS-06).
 *
 * pending ──start──▶ processing ──▶ ready
 *    ▲                   │
 *    └──── retry ── failed ◀┘   (pending can fail too: a job that never ran)
 *
 * Replacing the PDF sends any state back to pending.
 */
enum ProcessingStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Processing, self::Failed],
            self::Processing => [self::Ready, self::Failed],
            self::Failed => [self::Pending],
            self::Ready => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * Readers only ever see ready issues.
     */
    public function isPublic(): bool
    {
        return $this === self::Ready;
    }
}
