<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Site\UpdateSettings;

use Elaf\Settings\Application\SettingsPermission;
use Elaf\Settings\Application\Site\SettingsReader;
use Elaf\Settings\Application\Site\SettingsStore;
use Elaf\Settings\Application\Site\SiteSettings;
use Elaf\Settings\Domain\Events\SettingsChanged;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Application\Validation\Fields;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-SET-01, 02: checks every field, stores what changed and announces it
 * so cached settings are refreshed.
 */
final readonly class UpdateSettingsHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private SettingsReader $reader,
        private SettingsStore $store,
        private Transaction $transaction,
        private EventDispatcher $events,
        private Clock $clock,
    ) {}

    public function handle(UpdateSettingsCommand $command): void
    {
        if (! $this->authorizer->allows(SettingsPermission::ManageSettings->value)) {
            throw NotAuthorized::lacks(SettingsPermission::ManageSettings->value);
        }

        $new = $this->validate($command->settings)->toArray();
        $old = $this->reader->site()->toArray();
        $changed = array_values(array_filter(SiteSettings::KEYS, static fn (string $key): bool => $new[$key] !== $old[$key]));

        if ($changed === []) {
            return;
        }

        $this->transaction->run(function () use ($new, $changed, $command): void {
            $this->store->put(array_intersect_key($new, array_flip($changed)));
            $this->events->dispatch(new SettingsChanged($command->actorId, $changed, $this->clock->now()));
        });
    }

    private function validate(SiteSettings $s): SiteSettings
    {
        return new SiteSettings(
            name: Fields::required('name', $s->name, 60),
            logoSuffix: Fields::optional('logo_suffix', $s->logoSuffix, 40),
            motto: Fields::optional('motto', $s->motto, 120),
            description: Fields::optional('description', $s->description, 300),
            logoPath: self::path('logo_path', $s->logoPath),
            faviconPath: self::path('favicon_path', $s->faviconPath),
            defaultOgImagePath: self::path('default_og_image_path', $s->defaultOgImagePath),
            contactEmail: self::email($s->contactEmail),
            phone: self::phone($s->phone),
            address: Fields::optional('address', $s->address, 300),
            footerText: Fields::optional('footer_text', $s->footerText, 500),
            facebookUrl: Fields::httpUrl('facebook_url', $s->facebookUrl),
            whatsappUrl: Fields::httpUrl('whatsapp_url', $s->whatsappUrl),
            youtubeUrl: Fields::httpUrl('youtube_url', $s->youtubeUrl),
            xUrl: Fields::httpUrl('x_url', $s->xUrl),
            mastheadEditorInChief: Fields::optional('masthead_editor_in_chief', $s->mastheadEditorInChief, 120),
            mastheadEditorialAdvisor: Fields::optional('masthead_editorial_advisor', $s->mastheadEditorialAdvisor, 120),
            mastheadPublisher: Fields::optional('masthead_publisher', $s->mastheadPublisher, 120),
            analyticsId: self::analytics($s->analyticsId),
            requireContributorApproval: $s->requireContributorApproval,
            editorsCanPublishContributorArticles: $s->editorsCanPublishContributorArticles,
            guestBylineLabel: $s->guestBylineLabel,
        );
    }

    private static function email(?string $email): ?string
    {
        $email = Fields::optional('contact_email', $email, 190);

        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidInput::because('contact_email', 'invalid_email', 'The email address is not valid.');
        }

        return $email;
    }

    private static function phone(?string $phone): ?string
    {
        $phone = Fields::optional('phone', $phone, 30);

        if ($phone !== null && preg_match('/^\+?[0-9 ()\-]{6,30}$/', $phone) !== 1) {
            throw InvalidInput::because('phone', 'invalid_phone', 'The phone number is not valid.');
        }

        return $phone;
    }

    /**
     * A Google tag id (GA4 "G-…", or "GT-"/"AW-").
     */
    private static function analytics(?string $id): ?string
    {
        $id = Fields::optional('analytics_id', $id, 30);

        if ($id !== null && preg_match('/^(G|GT|AW)-[A-Z0-9]{4,20}$/', $id) !== 1) {
            throw InvalidInput::because('analytics_id', 'invalid_analytics_id', 'The analytics id is not valid.');
        }

        return $id;
    }

    /**
     * A relative path on the public disk, never outside it.
     */
    private static function path(string $field, ?string $path): ?string
    {
        $path = Fields::optional($field, $path, 255);

        if ($path !== null && (str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, '://'))) {
            throw InvalidInput::because($field, 'invalid_path', 'The file path is not valid.');
        }

        return $path;
    }
}
