<?php

declare(strict_types=1);

namespace Elaf\Engagement\Domain\Letter;

/**
 * FR-LET-02: pending → approved | rejected.
 */
enum LetterStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
