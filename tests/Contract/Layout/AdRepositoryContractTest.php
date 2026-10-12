<?php

declare(strict_types=1);

use Elaf\Layout\Domain\Ad\Ad;
use Elaf\Layout\Domain\Ad\AdCreative;
use Elaf\Layout\Domain\Ad\AdRepository;
use Elaf\Layout\Domain\Ad\AdSlot;
use Elaf\Layout\Domain\Ad\DateRange;
use Elaf\Layout\Domain\Ad\Exception\AdNotFound;
use Elaf\Layout\Infrastructure\Persistence\EloquentAdRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\InMemoryAds;

uses(RefreshDatabase::class);

dataset('ad repositories', [
    'in-memory' => fn (): AdRepository => new InMemoryAds,
    'eloquent' => fn (): AdRepository => new EloquentAdRepository(app(ConnectionInterface::class)),
]);

it('round-trips an ad, its schedule and creative; delete hides it', function (AdRepository $ads): void {
    $clock = new FrozenClock('2026-10-10 12:00:00');
    $starts = new DateTimeImmutable('2026-10-10 08:30:00', new DateTimeZone('UTC'));
    $ad = Ad::create($ads->nextId(), 'إعلان', AdSlot::ArticleSidebar, AdCreative::html('<p>عرض</p>'), null,
        new DateRange($starts, $starts->modify('+7 days')), 7, false, 1, $clock);
    $ads->save($ad);

    $loaded = $ads->get($ad->id());
    expect($loaded->title())->toBe('إعلان')
        ->and($loaded->slot())->toBe(AdSlot::ArticleSidebar)
        ->and($loaded->creative()->html)->toBe('<p>عرض</p>')
        ->and($loaded->targetUrl())->toBeNull()
        ->and($loaded->schedule()->equals(new DateRange($starts, $starts->modify('+7 days'))))->toBeTrue()
        ->and($loaded->weight())->toBe(7)
        ->and($loaded->isActive())->toBeFalse()
        ->and($loaded->releaseEvents())->toBe([]);

    $loaded->revise('صورة', AdSlot::Footer, AdCreative::image('ads/b.webp'), 'https://ex.example', 2, 1, $clock);
    $ads->save($loaded);
    expect($ads->get($ad->id())->creative()->imagePath)->toBe('ads/b.webp')
        ->and($ads->nextId()->value)->toBeGreaterThan($ad->id()->value);

    $ads->delete($ad->id());
    expect(fn () => $ads->get($ad->id()))->toThrow(AdNotFound::class);
})->with('ad repositories');
