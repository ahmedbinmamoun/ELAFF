<?php

declare(strict_types=1);

namespace Elaf\Contributors\Domain\Exception;

use Elaf\Shared\Domain\Exception\DomainException;

final class ContributorRuleViolation extends DomainException
{
    private function __construct(
        public readonly string $reason,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function invalidName(): self
    {
        return new self('invalid_display_name', 'A display name must be 1 to 120 characters.');
    }

    public static function invalidBio(): self
    {
        return new self('invalid_bio', 'A short bio of at most 1000 characters is required.');
    }

    public static function invalidPhone(): self
    {
        return new self('invalid_phone', 'The phone number is not valid.');
    }

    public static function rejectionReasonRequired(): self
    {
        return new self('rejection_reason_required', 'Rejecting a contributor requires a reason.');
    }

    public static function noPendingNameChange(): self
    {
        return new self('no_pending_name_change', 'There is no display name change to approve.');
    }
}
