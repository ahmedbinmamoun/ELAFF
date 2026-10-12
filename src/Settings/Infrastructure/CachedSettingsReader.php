<?php

declare(strict_types=1);

namespace Elaf\Settings\Infrastructure;

use Elaf\Settings\Application\Site\SettingsReader;
use Elaf\Settings\Application\Site\SettingsStore;
use Elaf\Settings\Application\Site\SiteSettings;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * FR-SET-02: stored settings read once and kept in the cache until
 * SettingsChanged (forget()). Defaults come from config/elaf.php, so a fresh
 * installation shows what .env says until an admin saves the form.
 */
final class CachedSettingsReader implements SettingsReader
{
    public const CACHE_KEY = 'elaf.settings.v1';

    private ?SiteSettings $memo = null;

    public function __construct(
        private readonly SettingsStore $store,
        private readonly Cache $cache,
        private readonly Config $config,
    ) {}

    public function site(): SiteSettings
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $stored = $this->cache->rememberForever(self::CACHE_KEY, fn (): array => $this->store->all());

        return $this->memo = SiteSettings::fromArray($stored, $this->defaults());
    }

    public function forget(): void
    {
        $this->memo = null;
        $this->cache->forget(self::CACHE_KEY);
    }

    private function defaults(): SiteSettings
    {
        $text = function (string $key): ?string {
            $value = $this->config->get('elaf.'.$key);

            return is_string($value) && trim($value) !== '' ? $value : null;
        };

        return new SiteSettings(
            name: $text('site.name') ?? 'إيلاف',
            logoSuffix: $text('site.logo_suffix'),
            motto: $text('site.motto'),
            description: $text('site.description'),
            contactEmail: $text('site.email'),
            facebookUrl: $text('site.social.facebook'),
            whatsappUrl: $text('site.social.whatsapp'),
            youtubeUrl: $text('site.social.youtube'),
            xUrl: $text('site.social.x'),
            mastheadEditorInChief: $text('site.masthead.editor_in_chief'),
            mastheadEditorialAdvisor: $text('site.masthead.editorial_advisor'),
            mastheadPublisher: $text('site.masthead.publisher'),
            requireContributorApproval: (bool) $this->config->get('elaf.contributors.require_account_approval', true),
            editorsCanPublishContributorArticles: (bool) $this->config->get('elaf.contributors.editor_can_publish', false),
            guestBylineLabel: (bool) $this->config->get('elaf.contributors.guest_byline_label', true),
        );
    }
}
