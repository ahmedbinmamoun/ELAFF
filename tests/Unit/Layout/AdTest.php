<?php

declare(strict_types=1);

use Elaf\Layout\Domain\Ad\Ad;
use Elaf\Layout\Domain\Ad\AdCreative;
use Elaf\Layout\Domain\Ad\AdId;
use Elaf\Layout\Domain\Ad\AdSlot;
use Elaf\Layout\Domain\Ad\DateRange;
use Elaf\Layout\Domain\Ad\Events\AdChanged;
use Elaf\Layout\Domain\Ad\Exception\InvalidAd;
use Tests\Support\Fakes\FrozenClock;

beforeEach(function (): void {
    $this->clock = new FrozenClock('2026-10-10 12:00:00');
    $this->ad = fn (array $o = []): Ad => Ad::create(
        AdId::fromInt(1),
        $o['title'] ?? 'إعلان بنك',
        $o['slot'] ?? AdSlot::HeaderBanner,
        $o['creative'] ?? AdCreative::image('ads/a.webp'),
        array_key_exists('target', $o) ? $o['target'] : 'https://bank.example/offer',
        $o['schedule'] ?? new DateRange,
        $o['weight'] ?? 1,
        $o['active'] ?? true,
        5,
        $this->clock,
    );
});

it('runs while active and inside its schedule (PRD §6.4)', function (): void {
    $now = $this->clock->now();
    $ad = ($this->ad)(['schedule' => new DateRange($now->modify('-1 day'), $now->modify('+1 day'))]);

    expect($ad->isRunningAt($now))->toBeTrue()
        ->and($ad->isRunningAt($now->modify('-2 days')))->toBeFalse()
        ->and($ad->isRunningAt($now->modify('+1 day')))->toBeFalse();

    $ad->deactivate(5, $this->clock);
    expect($ad->isRunningAt($now))->toBeFalse();
});

it('records changes only when something changes', function (): void {
    $ad = ($this->ad)();
    $ad->releaseEvents();

    $ad->activate(5, $this->clock);
    $ad->revise('إعلان بنك', AdSlot::HeaderBanner, AdCreative::image('ads/a.webp'), 'https://bank.example/offer', 1, 5, $this->clock);
    $ad->reschedule(new DateRange, 5, $this->clock);
    expect($ad->releaseEvents())->toBe([]);

    $ad->revise('إعلان جديد', AdSlot::Footer, AdCreative::html('<p>نص</p>'), null, 50, 5, $this->clock);
    $ad->deactivate(5, $this->clock);
    $ad->reschedule(new DateRange($this->clock->now()), 5, $this->clock);
    expect(array_map(static fn (AdChanged $e): string => $e->change, $ad->releaseEvents()))->toBe(['revised', 'deactivated', 'rescheduled'])
        ->and($ad->slot())->toBe(AdSlot::Footer)
        ->and($ad->creative()->isImage())->toBeFalse();
});

it('enforces the ad rules', function (Closure $make, string $reason): void {
    try {
        $make($this);
        $this->fail('No exception.');
    } catch (InvalidAd $e) {
        expect($e->reason)->toBe($reason);
    }
})->with([
    'no title' => [fn ($t) => ($t->ad)(['title' => '  ']), 'ad_title'],
    'image without target' => [fn ($t) => ($t->ad)(['target' => null]), 'ad_target_required'],
    'javascript target' => [fn ($t) => ($t->ad)(['target' => 'javascript:alert(1)']), 'ad_target_invalid'],
    'weight 0' => [fn ($t) => ($t->ad)(['weight' => 0]), 'ad_weight'],
    'weight 101' => [fn ($t) => ($t->ad)(['weight' => 101]), 'ad_weight'],
    'ends before start' => [fn () => new DateRange(new DateTimeImmutable('2026-10-10'), new DateTimeImmutable('2026-10-09')), 'ad_schedule_order'],
    'image outside disk' => [fn () => AdCreative::image('../secret.png'), 'ad_image_invalid'],
    'empty html' => [fn () => AdCreative::html(' '), 'ad_html_empty'],
]);

it('lets an HTML ad go without a target and reserves slot sizes', function (): void {
    expect(($this->ad)(['creative' => AdCreative::html('<b>عرض</b>'), 'target' => null])->targetUrl())->toBeNull()
        ->and([AdSlot::ArticleSidebar->width(), AdSlot::ArticleSidebar->height()])->toBe([300, 250]);
});
