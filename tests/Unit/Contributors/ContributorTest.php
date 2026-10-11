<?php

declare(strict_types=1);

use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Contributors\Domain\Contributor;
use Elaf\Contributors\Domain\ContributorProfile;
use Elaf\Contributors\Domain\ContributorStatus;
use Elaf\Contributors\Domain\DisplayName;
use Elaf\Contributors\Domain\Events\ContributorApproved;
use Elaf\Contributors\Domain\Events\ContributorEmailVerified;
use Elaf\Contributors\Domain\Events\ContributorRegistered;
use Elaf\Contributors\Domain\Events\ContributorRejected;
use Elaf\Contributors\Domain\Events\ContributorRenamed;
use Elaf\Contributors\Domain\Exception\ContributorRuleViolation;
use Elaf\Contributors\Domain\Exception\IllegalContributorTransition;
use Elaf\Identity\Domain\UserId;
use Tests\Support\Fakes\FrozenClock;

function a_contributor(FrozenClock $clock, ContributorStatus $until = ContributorStatus::PendingEmail): Contributor
{
    $c = Contributor::register(UserId::fromInt(10), new DisplayName('آمنة الطيب'), new ContributorProfile('كاتبة اقتصادية'), $clock);
    $editor = UserId::fromInt(2);

    match ($until) {
        ContributorStatus::PendingEmail => null,
        ContributorStatus::PendingApproval => $c->verifyEmail($clock),
        ContributorStatus::Active => [$c->verifyEmail($clock), $c->approve($editor, AuthorId::fromInt(5), $clock)],
        ContributorStatus::Rejected => [$c->verifyEmail($clock), $c->reject($editor, 'لا يناسب', $clock)],
        ContributorStatus::Suspended => [$c->verifyEmail($clock), $c->approve($editor, AuthorId::fromInt(5), $clock), $c->suspend($editor, $clock)],
    };
    $c->releaseEvents();

    return $c;
}

$actions = [
    'verify' => [fn (Contributor $c, FrozenClock $k) => $c->verifyEmail($k), [ContributorStatus::PendingEmail], ContributorStatus::PendingApproval],
    'approve' => [fn (Contributor $c, FrozenClock $k) => $c->approve(UserId::fromInt(2), AuthorId::fromInt(5), $k), [ContributorStatus::PendingApproval], ContributorStatus::Active],
    'reject' => [fn (Contributor $c, FrozenClock $k) => $c->reject(UserId::fromInt(2), 'سبب', $k), [ContributorStatus::PendingApproval], ContributorStatus::Rejected],
    'suspend' => [fn (Contributor $c, FrozenClock $k) => $c->suspend(UserId::fromInt(2), $k), [ContributorStatus::Active], ContributorStatus::Suspended],
    'reinstate' => [fn (Contributor $c, FrozenClock $k) => $c->reinstate(UserId::fromInt(2), $k), [ContributorStatus::Suspended], ContributorStatus::Active],
];
$cases = [];
foreach ($actions as $name => $action) {
    foreach (ContributorStatus::cases() as $from) {
        $cases["{$name} from {$from->value}"] = [...$action, $from];
    }
}

it('applies each account action only from its allowed state (FR-CON-02, 03, 12)', function (Closure $apply, array $allowed, ContributorStatus $to, ContributorStatus $from): void {
    $clock = new FrozenClock;
    $contributor = a_contributor($clock, $from);

    if (in_array($from, $allowed, true)) {
        $apply($contributor, $clock);
        expect($contributor->status())->toBe($to);

        return;
    }

    expect(fn () => $apply($contributor, $clock))->toThrow(IllegalContributorTransition::class);
})->with($cases);

it('signs in only while pending approval or active', function (ContributorStatus $status, bool $can): void {
    expect($status->canSignIn())->toBe($can)
        ->and($status->canWrite())->toBe($status === ContributorStatus::Active);
})->with([
    [ContributorStatus::PendingEmail, false],
    [ContributorStatus::PendingApproval, true],
    [ContributorStatus::Active, true],
    [ContributorStatus::Rejected, false],
    [ContributorStatus::Suspended, false],
]);

it('records registration, terms acceptance, verification and approval', function (): void {
    $clock = new FrozenClock;
    $c = Contributor::register(UserId::fromInt(10), new DisplayName('  آمنة   الطيب '), new ContributorProfile('نبذة', ' +249 912 345 678 '), $clock);

    expect($c->name()->value)->toBe('آمنة الطيب')
        ->and($c->profile()->phone)->toBe('+249 912 345 678')
        ->and($c->termsAcceptedAt())->toEqual($clock->now())
        ->and($c->releaseEvents()[0])->toBeInstanceOf(ContributorRegistered::class);

    $c->verifyEmail($clock);
    $c->approve(null, AuthorId::fromInt(7), $clock);
    $events = $c->releaseEvents();

    expect($events[0])->toBeInstanceOf(ContributorEmailVerified::class)
        ->and($events[1])->toBeInstanceOf(ContributorApproved::class)
        ->and($events[1]->actorId)->toBeNull()
        ->and($c->authorId()?->value)->toBe(7)
        ->and($c->approvedAt())->toEqual($clock->now());
});

it('needs a reason to reject', function (): void {
    $clock = new FrozenClock;
    $c = a_contributor($clock, ContributorStatus::PendingApproval);

    expect(fn () => $c->reject(UserId::fromInt(2), ' ', $clock))->toThrow(ContributorRuleViolation::class);

    $c->reject(UserId::fromInt(2), 'المقالات غير أصلية', $clock);
    expect($c->rejectionReason())->toBe('المقالات غير أصلية')
        ->and($c->releaseEvents()[0])->toBeInstanceOf(ContributorRejected::class);
});

it('holds a display name change until an editor approves it', function (): void {
    $clock = new FrozenClock;
    $c = a_contributor($clock, ContributorStatus::Active);

    $c->requestNameChange(new DisplayName('آمنة ط.'), $clock);
    expect($c->name()->value)->toBe('آمنة الطيب')->and($c->requestedName()?->value)->toBe('آمنة ط.');

    $c->approveNameChange(UserId::fromInt(2), $clock);
    expect($c->name()->value)->toBe('آمنة ط.')
        ->and($c->requestedName())->toBeNull()
        ->and(array_map(get_class(...), $c->releaseEvents()))->toContain(ContributorRenamed::class);

    expect(fn () => $c->approveNameChange(UserId::fromInt(2), $clock))->toThrow(ContributorRuleViolation::class);

    $c->requestNameChange(new DisplayName('اسم آخر'), $clock);
    $c->requestNameChange(new DisplayName('آمنة ط.'), $clock);
    expect($c->requestedName())->toBeNull();
});

it('validates names and profiles', function (Closure $build): void {
    $build();
})->with([
    'empty name' => [fn () => new DisplayName('  ')],
    'long name' => [fn () => new DisplayName(str_repeat('ا', 121))],
    'empty bio' => [fn () => new ContributorProfile(' ')],
    'long bio' => [fn () => new ContributorProfile(str_repeat('ا', 1001))],
    'bad phone' => [fn () => new ContributorProfile('نبذة', 'call me')],
])->throws(ContributorRuleViolation::class);
