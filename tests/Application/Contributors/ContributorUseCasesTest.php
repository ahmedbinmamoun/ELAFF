<?php

declare(strict_types=1);

use Elaf\Contributors\Application\Approve\ApproveContributorCommand;
use Elaf\Contributors\Application\Approve\ApproveContributorHandler;
use Elaf\Contributors\Application\ApproveNameChange\ApproveNameChangeCommand;
use Elaf\Contributors\Application\ApproveNameChange\ApproveNameChangeHandler;
use Elaf\Contributors\Application\ContributorPermission;
use Elaf\Contributors\Application\Events\RegistrationAttemptedForKnownEmail;
use Elaf\Contributors\Application\Register\RegisterContributorCommand;
use Elaf\Contributors\Application\Register\RegisterContributorHandler;
use Elaf\Contributors\Application\Reinstate\ReinstateContributorCommand;
use Elaf\Contributors\Application\Reinstate\ReinstateContributorHandler;
use Elaf\Contributors\Application\Reject\RejectContributorCommand;
use Elaf\Contributors\Application\Reject\RejectContributorHandler;
use Elaf\Contributors\Application\Suspend\SuspendContributorCommand;
use Elaf\Contributors\Application\Suspend\SuspendContributorHandler;
use Elaf\Contributors\Application\UpdateProfile\UpdateContributorProfileCommand;
use Elaf\Contributors\Application\UpdateProfile\UpdateContributorProfileHandler;
use Elaf\Contributors\Application\VerifyEmail\VerifyContributorEmailCommand;
use Elaf\Contributors\Application\VerifyEmail\VerifyContributorEmailHandler;
use Elaf\Contributors\Domain\ContributorStatus;
use Elaf\Contributors\Domain\Events\ContributorEmailVerified;
use Elaf\Contributors\Domain\Events\ContributorRegistered;
use Elaf\Contributors\Domain\Exception\IllegalContributorTransition;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Tests\Support\ContentKit;
use Tests\Support\Fakes\FakeCaptchaVerifier;

beforeEach(function (): void {
    $this->kit = new ContentKit;
    $this->kit->authorizer->grant(ContributorPermission::ManageContributors->value);
    $this->register = fn (array $overrides = []) => $this->kit->make(RegisterContributorHandler::class)->handle(new RegisterContributorCommand(...[
        'name' => 'آمنة الطيب',
        'email' => 'Amna@Example.com',
        'password' => 'a-long-password',
        'bio' => 'كاتبة في الشأن الاقتصادي',
        'acceptedTerms' => true,
        'captchaToken' => FakeCaptchaVerifier::VALID_TOKEN,
        ...$overrides,
    ]));
    $this->id = function (): UserId {
        return UserId::fromInt(array_key_last($this->kit->contributors->accounts));
    };
    $this->registered = function (): UserId {
        ($this->register)();
        $id = ($this->id)();
        $this->kit->make(VerifyContributorEmailHandler::class)->handle(new VerifyContributorEmailCommand($id->value));

        return $id;
    };
    $this->reason = static function (callable $work): ?string {
        try {
            $work();
        } catch (InvalidInput $e) {
            return $e->field.':'.$e->reason;
        }

        return null;
    };
});

it('registers a contributor pending email verification (FR-CON-01/02)', function (): void {
    ($this->register)();
    $id = ($this->id)();
    $contributor = $this->kit->contributors->get($id);

    expect($contributor->status())->toBe(ContributorStatus::PendingEmail)
        ->and($this->kit->contributors->accounts[$id->value]['email'])->toBe('amna@example.com')
        ->and($this->kit->contributors->accounts[$id->value]['hash'])->toBe('hashed:a-long-password')
        ->and($this->kit->events->ofType(ContributorRegistered::class))->toHaveCount(1);
});

it('answers a known email exactly like a new one, and only tells the owner (FR-CON-01)', function (): void {
    ($this->register)();
    $accounts = count($this->kit->contributors->accounts);

    ($this->register)(['name' => 'شخص آخر', 'email' => 'AMNA@example.com']);

    expect(count($this->kit->contributors->accounts))->toBe($accounts)
        ->and($this->kit->events->ofType(RegistrationAttemptedForKnownEmail::class))->toHaveCount(1);
});

it('rejects bots, missing terms and bad input on the right field', function (array $overrides, string $expected): void {
    expect(($this->reason)(fn () => ($this->register)($overrides)))->toBe($expected)
        ->and($this->kit->contributors->accounts)->toBe([]);
})->with([
    'captcha failed' => [['captchaToken' => 'bot'], 'captcha:captcha_failed'],
    'terms not accepted' => [['acceptedTerms' => false], 'terms:terms_required'],
    'blank name' => [['name' => ' '], 'name:invalid_display_name'],
    'no bio' => [['bio' => ''], 'bio:invalid_bio'],
    'bad phone' => [['phone' => 'abc'], 'phone:invalid_phone'],
    'bad email' => [['email' => 'nope'], 'email:invalid_email'],
    'short password' => [['password' => 'short'], 'password:too_short'],
]);

it('verifies the email once; later clicks are harmless', function (): void {
    $id = ($this->registered)();
    $this->kit->make(VerifyContributorEmailHandler::class)->handle(new VerifyContributorEmailCommand($id->value));

    expect($this->kit->contributors->get($id)->status())->toBe(ContributorStatus::PendingApproval)
        ->and($this->kit->events->ofType(ContributorEmailVerified::class))->toHaveCount(1)
        ->and($this->kit->roles->has($id, Role::Contributor))->toBeFalse();
});

it('activates on verification when account approval is switched off (FR-CON-03)', function (): void {
    $this->kit->settings->requireApproval = false;
    $id = ($this->registered)();
    $contributor = $this->kit->contributors->get($id);

    expect($contributor->status())->toBe(ContributorStatus::Active)
        ->and($contributor->approvedBy())->toBeNull()
        ->and($contributor->authorId())->not->toBeNull()
        ->and($this->kit->roles->has($id, Role::Contributor))->toBeTrue();
});

it('approves: linked author with a unique slug and the contributor role (FR-CON-04)', function (): void {
    $this->kit->author('آمنة الطيب');
    $id = ($this->registered)();

    $this->kit->make(ApproveContributorHandler::class)->handle(new ApproveContributorCommand(2, $id->value));
    $contributor = $this->kit->contributors->get($id);
    $author = $this->kit->authors->find($contributor->authorId() ?? throw new LogicException);

    expect($contributor->status())->toBe(ContributorStatus::Active)
        ->and($contributor->approvedBy()?->value)->toBe(2)
        ->and($author?->data->slug->value)->toBe('آمنة-الطيب-2')
        ->and($author?->data->userId)->toEqual($id)
        ->and($this->kit->roles->has($id, Role::Contributor))->toBeTrue();
});

it('rejects with a reason, and requires the permission for every editor action', function (): void {
    $id = ($this->registered)();
    $this->kit->make(RejectContributorHandler::class)->handle(new RejectContributorCommand(2, $id->value, 'لا يناسب خط الصحيفة'));
    expect($this->kit->contributors->get($id)->rejectionReason())->toBe('لا يناسب خط الصحيفة');

    $this->kit->authorizer->revoke(ContributorPermission::ManageContributors->value);
    expect(fn () => $this->kit->make(ApproveContributorHandler::class)->handle(new ApproveContributorCommand(2, $id->value)))
        ->toThrow(NotAuthorized::class);
});

it('suspends and reinstates, removing and restoring the right to write (FR-CON-12)', function (): void {
    $id = ($this->registered)();
    $this->kit->make(ApproveContributorHandler::class)->handle(new ApproveContributorCommand(2, $id->value));

    $this->kit->make(SuspendContributorHandler::class)->handle(new SuspendContributorCommand(2, $id->value));
    expect($this->kit->contributors->get($id)->status())->toBe(ContributorStatus::Suspended)
        ->and($this->kit->roles->has($id, Role::Contributor))->toBeFalse();

    expect(fn () => $this->kit->make(ApproveContributorHandler::class)->handle(new ApproveContributorCommand(2, $id->value)))
        ->toThrow(IllegalContributorTransition::class);

    $this->kit->make(ReinstateContributorHandler::class)->handle(new ReinstateContributorCommand(2, $id->value));
    expect($this->kit->contributors->get($id)->status())->toBe(ContributorStatus::Active)
        ->and($this->kit->roles->has($id, Role::Contributor))->toBeTrue();
});

it('updates the profile at once but the name only after approval, renaming the byline too', function (): void {
    $id = ($this->registered)();
    $this->kit->make(ApproveContributorHandler::class)->handle(new ApproveContributorCommand(2, $id->value));

    $this->kit->make(UpdateContributorProfileHandler::class)->handle(new UpdateContributorProfileCommand($id->value, 'آمنة ط.', 'نبذة جديدة', '0912345678'));
    $contributor = $this->kit->contributors->get($id);
    expect($contributor->profile()->bio)->toBe('نبذة جديدة')
        ->and($contributor->name()->value)->toBe('آمنة الطيب')
        ->and($contributor->requestedName()?->value)->toBe('آمنة ط.');

    $this->kit->make(ApproveNameChangeHandler::class)->handle(new ApproveNameChangeCommand(2, $id->value));
    $contributor = $this->kit->contributors->get($id);
    expect($contributor->name()->value)->toBe('آمنة ط.')
        ->and($this->kit->authors->find($contributor->authorId() ?? throw new LogicException)?->data->name)->toBe('آمنة ط.');
});
