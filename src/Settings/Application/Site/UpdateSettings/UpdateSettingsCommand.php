<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Site\UpdateSettings;

use Elaf\Settings\Application\Site\SiteSettings;

/**
 * The whole settings form; $settings holds the raw values to check.
 */
final readonly class UpdateSettingsCommand
{
    public function __construct(
        public int $actorId,
        public SiteSettings $settings,
    ) {}
}
