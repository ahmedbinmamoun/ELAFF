<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Ad;

use Elaf\Layout\Application\LayoutPermission;
use Elaf\Layout\Domain\Ad\AdCreative;
use Elaf\Layout\Domain\Ad\AdSlot;
use Elaf\Layout\Domain\Ad\DateRange;
use Elaf\Layout\Domain\Ad\Exception\InvalidAd;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Html\HtmlSanitizer;
use Elaf\Shared\Application\Html\SanitizerProfile;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Domain\Text\OptionalText;

/**
 * Turns the ad form into domain values, reporting problems on the form's
 * fields. HTML is sanitized unless the actor may place raw HTML (Admin,
 * FR-ADS-02: e.g. an ad network's tag).
 */
final readonly class AdFields
{
    /** Domain reason → form field. */
    private const FIELDS = [
        'ad_title' => 'title',
        'ad_target_required' => 'target_url',
        'ad_target_invalid' => 'target_url',
        'ad_weight' => 'weight',
        'ad_schedule_order' => 'ends_at',
        'ad_image_invalid' => 'image_path',
        'ad_html_empty' => 'html',
        'ad_html_too_long' => 'html',
    ];

    public function __construct(
        private Authorizer $authorizer,
        private HtmlSanitizer $sanitizer,
    ) {}

    public function requireManage(): void
    {
        if (! $this->authorizer->allows(LayoutPermission::ManageAds->value)) {
            throw NotAuthorized::lacks(LayoutPermission::ManageAds->value);
        }
    }

    public function slot(AdInput $input): AdSlot
    {
        return AdSlot::tryFrom($input->slot) ?? throw InvalidInput::because('slot', 'unknown_ad_slot', 'Unknown ad slot.');
    }

    public function creative(AdInput $input): AdCreative
    {
        $image = OptionalText::normalize($input->imagePath);
        $html = OptionalText::normalize($input->html);

        return $this->guard(function () use ($image, $html): AdCreative {
            if ($image !== null) {
                return AdCreative::image($image);
            }

            if ($html === null) {
                throw InvalidInput::because('image_path', 'ad_creative_required', 'Upload an image or paste HTML.');
            }

            return AdCreative::html($this->authorizer->allows(LayoutPermission::RawAdHtml->value)
                ? $html
                : $this->sanitizer->sanitize($html, SanitizerProfile::Staff));
        });
    }

    public function schedule(AdInput $input): DateRange
    {
        return $this->guard(static fn (): DateRange => new DateRange($input->startsAt, $input->endsAt));
    }

    public function target(AdInput $input): ?string
    {
        return OptionalText::normalize($input->targetUrl);
    }

    /**
     * Runs domain construction, mapping InvalidAd to a field error.
     *
     * @template T
     *
     * @param  callable(): T  $build
     * @return T
     */
    public function guard(callable $build): mixed
    {
        try {
            return $build();
        } catch (InvalidAd $e) {
            throw InvalidInput::because(self::FIELDS[$e->reason] ?? 'title', $e->reason, $e->getMessage());
        }
    }
}
