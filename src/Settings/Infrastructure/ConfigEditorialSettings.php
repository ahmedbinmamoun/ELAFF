<?php

declare(strict_types=1);

namespace Elaf\Settings\Infrastructure;

use Elaf\Content\Application\Article\EditorialSettings;
use Elaf\Contributors\Application\ContributorSettings;
use Illuminate\Contracts\Config\Repository;

/**
 * Editorial switches from config/elaf.php until the Settings module stores
 * them in the database (Phase 5).
 */
final readonly class ConfigEditorialSettings implements ContributorSettings, EditorialSettings
{
    public function __construct(private Repository $config) {}

    public function editorsCanPublishContributorArticles(): bool
    {
        return (bool) $this->config->get('elaf.contributors.editor_can_publish', false);
    }

    public function requireAccountApproval(): bool
    {
        return (bool) $this->config->get('elaf.contributors.require_account_approval', true);
    }

    public function showGuestBylineLabel(): bool
    {
        return (bool) $this->config->get('elaf.contributors.guest_byline_label', true);
    }
}
