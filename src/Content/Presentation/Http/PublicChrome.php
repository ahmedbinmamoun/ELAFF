<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Http;

use Elaf\Content\Application\Query\ArticleLink;
use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Layout\Presentation\Menus\PublicMenus;
use Elaf\Settings\Application\Site\SettingsReader;
use Elaf\Shared\Domain\Time\Clock;
use Elaf\Shared\Presentation\ArabicDate;
use Elaf\Shared\Presentation\PublicMedia;

/**
 * Data every public page shows (header, ticker, footer), loaded at most once
 * per request however many views ask for it.
 */
final class PublicChrome
{
    /** @var array<string, mixed>|null */
    private ?array $data = null;

    public function __construct(
        private readonly ArticleQueries $articles,
        private readonly Clock $clock,
        private readonly SettingsReader $settings,
        private readonly PublicMenus $menus,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data ??= [
            'site' => $this->site(),
            'menus' => $this->menus->all(),
            'breaking' => $this->breaking(),
            'today' => ArabicDate::withWeekday($this->clock->now()),
            'year' => $this->clock->now()->format('Y'),
        ];
    }

    /**
     * @return list<ArticleLink>
     */
    private function breaking(): array
    {
        return $this->articles->breaking($this->clock->now(), 10);
    }

    /**
     * Site identity from the settings (FR-SET-01), in the shape the views use.
     *
     * @return array<string, mixed>
     */
    private function site(): array
    {
        $s = $this->settings->site();

        return [
            'name' => $s->name,
            'logo_suffix' => $s->logoSuffix,
            'motto' => $s->motto,
            'description' => $s->description,
            'logo_url' => $s->logoPath === null ? null : PublicMedia::url($s->logoPath),
            'favicon_url' => $s->faviconPath === null ? null : PublicMedia::url($s->faviconPath),
            'og_image_url' => $s->defaultOgImagePath === null ? null : PublicMedia::url($s->defaultOgImagePath),
            'email' => $s->contactEmail,
            'phone' => $s->phone,
            'address' => $s->address,
            'footer_text' => $s->footerText,
            'analytics_id' => $s->analyticsId,
            'masthead' => [
                'editor_in_chief' => $s->mastheadEditorInChief,
                'editorial_advisor' => $s->mastheadEditorialAdvisor,
                'publisher' => $s->mastheadPublisher,
            ],
            'social' => [
                'facebook' => $s->facebookUrl,
                'whatsapp' => $s->whatsappUrl,
                'youtube' => $s->youtubeUrl,
                'x' => $s->xUrl,
            ],
        ];
    }
}
