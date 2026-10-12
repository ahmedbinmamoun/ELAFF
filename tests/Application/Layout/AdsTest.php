<?php

declare(strict_types=1);

use Elaf\Layout\Application\Ad\AdFields;
use Elaf\Layout\Application\Ad\AdInput as Input;
use Elaf\Layout\Application\Ad\AdUnitOfWork;
use Elaf\Layout\Application\Ad\CreateAd\CreateAdCommand;
use Elaf\Layout\Application\Ad\CreateAd\CreateAdHandler;
use Elaf\Layout\Application\Ad\DeleteAd\DeleteAdCommand;
use Elaf\Layout\Application\Ad\DeleteAd\DeleteAdHandler;
use Elaf\Layout\Application\Ad\RecordClick\RecordAdClickHandler;
use Elaf\Layout\Application\Ad\SelectForSlot\SelectAdForSlotHandler;
use Elaf\Layout\Application\Ad\UpdateAd\UpdateAdCommand;
use Elaf\Layout\Application\Ad\UpdateAd\UpdateAdHandler;
use Elaf\Layout\Application\LayoutPermission;
use Elaf\Layout\Domain\Ad\AdId;
use Elaf\Layout\Domain\Ad\Events\AdChanged;
use Elaf\Layout\Domain\Ad\Exception\AdNotFound;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Infrastructure\Html\HtmlPurifierSanitizer;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAds;
use Tests\Support\Fakes\InMemoryAuthorizer;
use Tests\Support\Fakes\RecordingEventDispatcher;

beforeEach(function (): void {
    $this->ads = new InMemoryAds;
    $this->clock = new FrozenClock('2026-10-10 12:00:00');
    $this->authorizer = new InMemoryAuthorizer([LayoutPermission::ManageAds->value]);
    $this->events = new RecordingEventDispatcher;
    $fields = new AdFields($this->authorizer, new HtmlPurifierSanitizer);
    $uow = new AdUnitOfWork($this->ads, new ImmediateTransaction, $this->events);
    $this->create = fn (Input $i) => (new CreateAdHandler($fields, $uow, $this->clock))->handle(new CreateAdCommand(2, $i))->value;
    $this->update = fn (int $id, Input $i) => (new UpdateAdHandler($fields, $uow, $this->clock))->handle(new UpdateAdCommand(2, $id, $i));
    $this->delete = fn (int $id) => (new DeleteAdHandler($fields, $this->ads, new ImmediateTransaction, $this->events, $this->clock))->handle(new DeleteAdCommand(2, $id));
    $this->select = fn (string $slot) => (new SelectAdForSlotHandler($this->ads, $this->ads, $this->clock))->handle($slot);
    $this->image = static fn (string $slot = 'header_banner', int $weight = 1, array $more = []): Input => new Input(...[
        'title' => 'إعلان', 'slot' => $slot, 'imagePath' => 'ads/a.webp', 'targetUrl' => 'https://ex.example', 'weight' => $weight, ...$more,
    ]);
    $this->reason = static function (callable $work): string {
        try {
            $work();
        } catch (InvalidInput $e) {
            return $e->field.':'.$e->reason;
        }

        return 'none';
    };
});

it('creates, updates and deletes ads (FR-ADS-01, 02)', function (): void {
    $id = ($this->create)(($this->image)());
    ($this->update)($id, new Input('إعلان معدل', 'footer', html: '<p>عرض</p>', weight: 3, isActive: false));

    $ad = $this->ads->get(AdId::fromInt($id));
    expect($ad->title())->toBe('إعلان معدل')
        ->and($ad->slot()->value)->toBe('footer')
        ->and($ad->creative()->html)->toBe('<p>عرض</p>')
        ->and($ad->isActive())->toBeFalse();

    ($this->delete)($id);
    expect(fn () => $this->ads->get(AdId::fromInt($id)))->toThrow(AdNotFound::class)
        ->and(array_map(static fn (AdChanged $e): string => $e->change, $this->events->ofType(AdChanged::class)))
        ->toBe(['created', 'revised', 'deactivated', 'deleted']);
});

it('sanitizes ad HTML unless the actor may place raw HTML (FR-ADS-02)', function (): void {
    $snippet = '<div class="ad">عرض</div><script src="https://ads.example/tag.js"></script>';

    $editor = ($this->create)(new Input('شبكة', 'footer', html: $snippet));
    $this->authorizer->grant(LayoutPermission::RawAdHtml->value);
    $admin = ($this->create)(new Input('شبكة', 'footer', html: $snippet));

    expect($this->ads->get(AdId::fromInt($editor))->creative()->html)->not->toContain('<script')
        ->and($this->ads->get(AdId::fromInt($admin))->creative()->html)->toBe($snippet);
});

it('reports problems on the form fields', function (Input $input, string $expected): void {
    expect(($this->reason)(fn () => ($this->create)($input)))->toBe($expected);
})->with([
    'unknown slot' => [new Input('ا', 'sidebar', imagePath: 'ads/a.webp', targetUrl: 'https://ex.example'), 'slot:unknown_ad_slot'],
    'no creative' => [new Input('ا', 'footer'), 'image_path:ad_creative_required'],
    'image without target' => [new Input('ا', 'footer', imagePath: 'ads/a.webp'), 'target_url:ad_target_required'],
    'bad weight' => [new Input('ا', 'footer', html: '<p>ب</p>', weight: 500), 'weight:ad_weight'],
    'bad schedule' => [new Input('ا', 'footer', html: '<p>ب</p>', startsAt: new DateTimeImmutable('2026-10-10'), endsAt: new DateTimeImmutable('2026-10-01')), 'ends_at:ad_schedule_order'],
]);

it('keeps ads for the Editor-in-Chief and Admin', function (): void {
    $this->authorizer->revoke(LayoutPermission::ManageAds->value);

    expect(fn () => ($this->create)(($this->image)()))->toThrow(NotAuthorized::class);
});

it('rotates by weight among the ads running now in the slot', function (): void {
    $light = ($this->create)(($this->image)('header_banner', 1));
    $heavy = ($this->create)(($this->image)('header_banner', 3));
    ($this->create)(($this->image)('footer', 50));
    ($this->create)(($this->image)('header_banner', 90, ['isActive' => false]));
    ($this->create)(($this->image)('header_banner', 90, ['startsAt' => new DateTimeImmutable('2026-11-01')]));
    ($this->create)(($this->image)('header_banner', 90, ['endsAt' => new DateTimeImmutable('2026-10-01')]));

    $this->ads->rolls = [1, 2, 4];
    expect(($this->select)('header_banner')?->id)->toBe($light)
        ->and($this->ads->lastMax)->toBe(4)
        ->and(($this->select)('header_banner')?->id)->toBe($heavy)
        ->and(($this->select)('header_banner')?->id)->toBe($heavy)
        ->and(($this->select)('article_sidebar'))->toBeNull();
});

it('counts clicks', function (): void {
    $id = ($this->create)(($this->image)());
    $click = new RecordAdClickHandler($this->ads);

    $click->handle($id);
    $click->handle($id);

    expect($this->ads->clicks[$id])->toBe(2);
});
