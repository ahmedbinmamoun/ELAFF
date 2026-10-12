<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Site;

/**
 * Current settings: stored values over the installation defaults. Cached by
 * the implementation and refreshed on SettingsChanged (FR-SET-02).
 */
interface SettingsReader
{
    public function site(): SiteSettings;
}
