<?php

declare(strict_types=1);

namespace Elaf\Layout\Domain\Ad;

use DateTimeImmutable;
use Elaf\Layout\Domain\Ad\Events\AdChanged;
use Elaf\Layout\Domain\Ad\Exception\InvalidAd;
use Elaf\Shared\Domain\Event\RecordsEvents;
use Elaf\Shared\Domain\Time\Clock;

/**
 * An advertisement (PRD §6.4, FR-ADS-01..03). Clicks are counted in the read
 * model, not here.
 */
final class Ad
{
    use RecordsEvents;

    public const MIN_WEIGHT = 1;

    public const MAX_WEIGHT = 100;

    private function __construct(
        private readonly AdId $id,
        private string $title,
        private AdSlot $slot,
        private AdCreative $creative,
        private ?string $targetUrl,
        private DateRange $schedule,
        private int $weight,
        private bool $active,
    ) {
        self::check($title, $creative, $targetUrl, $weight);
    }

    public static function create(
        AdId $id,
        string $title,
        AdSlot $slot,
        AdCreative $creative,
        ?string $targetUrl,
        DateRange $schedule,
        int $weight,
        bool $active,
        int $actorId,
        Clock $clock,
    ): self {
        $ad = new self($id, trim($title), $slot, $creative, $targetUrl, $schedule, $weight, $active);
        $ad->recordThat(new AdChanged($id, 'created', $actorId, $clock->now()));

        return $ad;
    }

    /**
     * Rebuilds a persisted ad. Repositories only; records no events.
     */
    public static function reconstitute(
        AdId $id,
        string $title,
        AdSlot $slot,
        AdCreative $creative,
        ?string $targetUrl,
        DateRange $schedule,
        int $weight,
        bool $active,
    ): self {
        return new self($id, $title, $slot, $creative, $targetUrl, $schedule, $weight, $active);
    }

    public function revise(string $title, AdSlot $slot, AdCreative $creative, ?string $targetUrl, int $weight, int $actorId, Clock $clock): void
    {
        $title = trim($title);
        self::check($title, $creative, $targetUrl, $weight);

        if ($title === $this->title && $slot === $this->slot && $creative->equals($this->creative) && $targetUrl === $this->targetUrl && $weight === $this->weight) {
            return;
        }

        [$this->title, $this->slot, $this->creative, $this->targetUrl, $this->weight] = [$title, $slot, $creative, $targetUrl, $weight];
        $this->recordThat(new AdChanged($this->id, 'revised', $actorId, $clock->now()));
    }

    public function reschedule(DateRange $schedule, int $actorId, Clock $clock): void
    {
        if ($schedule->equals($this->schedule)) {
            return;
        }

        $this->schedule = $schedule;
        $this->recordThat(new AdChanged($this->id, 'rescheduled', $actorId, $clock->now()));
    }

    public function activate(int $actorId, Clock $clock): void
    {
        if (! $this->active) {
            $this->active = true;
            $this->recordThat(new AdChanged($this->id, 'activated', $actorId, $clock->now()));
        }
    }

    public function deactivate(int $actorId, Clock $clock): void
    {
        if ($this->active) {
            $this->active = false;
            $this->recordThat(new AdChanged($this->id, 'deactivated', $actorId, $clock->now()));
        }
    }

    public function isRunningAt(DateTimeImmutable $moment): bool
    {
        return $this->active && $this->schedule->contains($moment);
    }

    public function id(): AdId
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function slot(): AdSlot
    {
        return $this->slot;
    }

    public function creative(): AdCreative
    {
        return $this->creative;
    }

    public function targetUrl(): ?string
    {
        return $this->targetUrl;
    }

    public function schedule(): DateRange
    {
        return $this->schedule;
    }

    public function weight(): int
    {
        return $this->weight;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * An image ad is a link: it needs where to go. An HTML ad carries its
     * own links, so the target is optional.
     */
    private static function check(string $title, AdCreative $creative, ?string $targetUrl, int $weight): void
    {
        if ($title === '' || mb_strlen($title) > 150) {
            throw InvalidAd::because('ad_title', 'An ad needs a title of at most 150 characters.');
        }

        if ($creative->isImage() && $targetUrl === null) {
            throw InvalidAd::because('ad_target_required', 'An image ad needs a target URL.');
        }

        if ($targetUrl !== null && preg_match('#^https?://[^\s]+$#i', $targetUrl) !== 1) {
            throw InvalidAd::because('ad_target_invalid', 'The target must be an http(s) URL.');
        }

        if ($weight < self::MIN_WEIGHT || $weight > self::MAX_WEIGHT) {
            throw InvalidAd::because('ad_weight', sprintf('Weight must be %d..%d.', self::MIN_WEIGHT, self::MAX_WEIGHT));
        }
    }
}
