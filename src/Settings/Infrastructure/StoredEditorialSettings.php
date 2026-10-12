<?php

declare(strict_types=1);

namespace Elaf\Settings\Infrastructure;

use Elaf\Content\Application\Article\EditorialSettings;
use Elaf\Contributors\Application\ContributorSettings;
use Elaf\Settings\Application\Site\SettingsReader;

/**
 * The editorial switches of other modules, read from the settings.
 */
final readonly class StoredEditorialSettings implements ContributorSettings, EditorialSettings
{
    public function __construct(private SettingsReader $settings) {}

    public function editorsCanPublishContributorArticles(): bool
    {
        return $this->settings->site()->editorsCanPublishContributorArticles;
    }

    public function requireAccountApproval(): bool
    {
        return $this->settings->site()->requireContributorApproval;
    }

    public function showGuestBylineLabel(): bool
    {
        return $this->settings->site()->guestBylineLabel;
    }
}
