<?php

declare(strict_types=1);

namespace Elaf\Contributors\Domain;

use Elaf\Contributors\Domain\Exception\ContributorRuleViolation;
use Elaf\Shared\Domain\Text\OptionalText;

/**
 * What a contributor may change freely (everything but the display name).
 */
final readonly class ContributorProfile
{
    public const MAX_BIO = 1000;

    public string $bio;

    public ?string $phone;

    public ?string $photoPath;

    public function __construct(string $bio, ?string $phone = null, ?string $photoPath = null)
    {
        $bio = trim($bio);
        $phone = OptionalText::normalize($phone);

        if ($bio === '' || mb_strlen($bio) > self::MAX_BIO) {
            throw ContributorRuleViolation::invalidBio();
        }

        if ($phone !== null && preg_match('/^\+?[0-9 ()\-]{6,30}$/', $phone) !== 1) {
            throw ContributorRuleViolation::invalidPhone();
        }

        $this->bio = $bio;
        $this->phone = $phone;
        $this->photoPath = OptionalText::normalize($photoPath);
    }
}
