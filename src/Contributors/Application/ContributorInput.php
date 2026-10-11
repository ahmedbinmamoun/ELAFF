<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application;

use Elaf\Contributors\Domain\ContributorProfile;
use Elaf\Contributors\Domain\DisplayName;
use Elaf\Contributors\Domain\Exception\ContributorRuleViolation;
use Elaf\Shared\Application\Validation\InvalidInput;

/**
 * Builds the domain values from form input, reporting failures on the field.
 */
final class ContributorInput
{
    private const FIELDS = [
        'invalid_display_name' => 'name',
        'invalid_bio' => 'bio',
        'invalid_phone' => 'phone',
    ];

    public static function name(string $name): DisplayName
    {
        return self::guard(static fn (): DisplayName => new DisplayName($name));
    }

    public static function profile(string $bio, ?string $phone, ?string $photoPath): ContributorProfile
    {
        return self::guard(static fn (): ContributorProfile => new ContributorProfile($bio, $phone, $photoPath));
    }

    /**
     * @template T
     *
     * @param  callable(): T  $build
     * @return T
     */
    private static function guard(callable $build): mixed
    {
        try {
            return $build();
        } catch (ContributorRuleViolation $e) {
            throw InvalidInput::because(self::FIELDS[$e->reason] ?? 'contributor', $e->reason, $e->getMessage());
        }
    }
}
