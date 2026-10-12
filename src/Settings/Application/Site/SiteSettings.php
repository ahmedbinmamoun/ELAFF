<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Site;

/**
 * The site's editable settings (FR-SET-01) with their stable storage keys.
 * File fields hold paths on the public disk.
 */
final readonly class SiteSettings
{
    public const KEYS = [
        'name', 'logo_suffix', 'motto', 'description', 'logo_path', 'favicon_path', 'default_og_image_path',
        'contact_email', 'phone', 'address', 'footer_text',
        'facebook_url', 'whatsapp_url', 'youtube_url', 'x_url',
        'masthead_editor_in_chief', 'masthead_editorial_advisor', 'masthead_publisher',
        'analytics_id',
        'contributors_require_account_approval', 'contributors_editor_can_publish', 'contributors_guest_byline_label',
    ];

    public function __construct(
        public string $name,
        public ?string $logoSuffix = null,
        public ?string $motto = null,
        public ?string $description = null,
        public ?string $logoPath = null,
        public ?string $faviconPath = null,
        public ?string $defaultOgImagePath = null,
        public ?string $contactEmail = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $footerText = null,
        public ?string $facebookUrl = null,
        public ?string $whatsappUrl = null,
        public ?string $youtubeUrl = null,
        public ?string $xUrl = null,
        public ?string $mastheadEditorInChief = null,
        public ?string $mastheadEditorialAdvisor = null,
        public ?string $mastheadPublisher = null,
        public ?string $analyticsId = null,
        public bool $requireContributorApproval = true,
        public bool $editorsCanPublishContributorArticles = false,
        public bool $guestBylineLabel = true,
    ) {}

    /**
     * Values by storage key.
     *
     * @return array<string, string|bool|null>
     */
    public function toArray(): array
    {
        return array_combine(self::KEYS, [
            $this->name, $this->logoSuffix, $this->motto, $this->description, $this->logoPath, $this->faviconPath, $this->defaultOgImagePath,
            $this->contactEmail, $this->phone, $this->address, $this->footerText,
            $this->facebookUrl, $this->whatsappUrl, $this->youtubeUrl, $this->xUrl,
            $this->mastheadEditorInChief, $this->mastheadEditorialAdvisor, $this->mastheadPublisher,
            $this->analyticsId,
            $this->requireContributorApproval, $this->editorsCanPublishContributorArticles, $this->guestBylineLabel,
        ]);
    }

    /**
     * Stored values over $defaults. A stored empty value clears a default.
     *
     * @param  array<string, mixed>  $values
     */
    public static function fromArray(array $values, self $defaults): self
    {
        $d = $defaults->toArray();
        $text = static function (string $key) use ($values, $d): ?string {
            $value = array_key_exists($key, $values) ? $values[$key] : $d[$key];

            return is_string($value) && trim($value) !== '' ? $value : null;
        };
        $flag = static fn (string $key): bool => is_bool($values[$key] ?? null) ? $values[$key] : (bool) $d[$key];

        return new self(
            name: $text('name') ?? $defaults->name,
            logoSuffix: $text('logo_suffix'),
            motto: $text('motto'),
            description: $text('description'),
            logoPath: $text('logo_path'),
            faviconPath: $text('favicon_path'),
            defaultOgImagePath: $text('default_og_image_path'),
            contactEmail: $text('contact_email'),
            phone: $text('phone'),
            address: $text('address'),
            footerText: $text('footer_text'),
            facebookUrl: $text('facebook_url'),
            whatsappUrl: $text('whatsapp_url'),
            youtubeUrl: $text('youtube_url'),
            xUrl: $text('x_url'),
            mastheadEditorInChief: $text('masthead_editor_in_chief'),
            mastheadEditorialAdvisor: $text('masthead_editorial_advisor'),
            mastheadPublisher: $text('masthead_publisher'),
            analyticsId: $text('analytics_id'),
            requireContributorApproval: $flag('contributors_require_account_approval'),
            editorsCanPublishContributorArticles: $flag('contributors_editor_can_publish'),
            guestBylineLabel: $flag('contributors_guest_byline_label'),
        );
    }
}
