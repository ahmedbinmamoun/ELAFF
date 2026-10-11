<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Contributors\Infrastructure\Mail\ContributorMailer;
use Elaf\Contributors\Infrastructure\Mail\ElafMail;
use Elaf\Contributors\Infrastructure\Persistence\ContributorProfileModel;
use Elaf\Contributors\Presentation\Filament\Auth\RegisterContributor;
use Elaf\Identity\Application\Role;
use Elaf\Shared\Application\Captcha\CaptchaVerifier;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;
use Tests\Support\Fakes\FakeCaptchaVerifier;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
    Filament::setCurrentPanel('writer');
    Mail::fake();
    $this->app->instance(CaptchaVerifier::class, new FakeCaptchaVerifier);
    $this->form = static fn (array $overrides = []): array => [
        'name' => 'آمنة الطيب',
        'email' => 'amna@example.com',
        'password' => 'a-long-password',
        'passwordConfirmation' => 'a-long-password',
        'bio' => 'كاتبة في الشأن الاقتصادي',
        'terms' => true,
        'captcha_token' => FakeCaptchaVerifier::VALID_TOKEN,
        ...$overrides,
    ];
});

it('serves the registration page at /writers/register, in Arabic', function (): void {
    $this->get('/writers/register')->assertRedirect('/writer/register');
    $this->get('/writer/register')->assertOk()->assertSee('dir="rtl"', false)->assertSee(__('contributors.fields.terms'));
});

it('registers a contributor pending email verification and sends the link (FR-CON-01/02)', function (): void {
    Livewire::test(RegisterContributor::class)
        ->fillForm(($this->form)())
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect(Filament::getLoginUrl())
        ->assertNotified(__('contributors.register.received'));

    $user = User::query()->where('email', 'amna@example.com')->sole();
    expect($user->type)->toBe('contributor')
        ->and($user->status)->toBe('pending_email')
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->roles)->toBeEmpty()
        ->and(ContributorProfileModel::query()->where('user_id', $user->id)->value('terms_accepted_at'))->not->toBeNull();
    expect(auth()->check())->toBeFalse();

    Mail::assertQueued(ElafMail::class, static fn (ElafMail $mail): bool => $mail->hasTo('amna@example.com')
        && $mail->subjectLine === __('mail.verify.subject')
        && str_contains((string) $mail->actionUrl, '/writer/verify-email/'.$user->id.'/'));
});

it('gives the same answer for a known email and only mails its owner (no enumeration)', function (): void {
    $existing = AdminFixtures::user(Role::Editor);

    Livewire::test(RegisterContributor::class)
        ->fillForm(($this->form)(['email' => strtoupper($existing->email)]))
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect(Filament::getLoginUrl())
        ->assertNotified(__('contributors.register.received'));

    expect(User::query()->count())->toBe(1);
    Mail::assertQueued(ElafMail::class, static fn (ElafMail $mail): bool => $mail->hasTo(strtolower($existing->email))
        && $mail->subjectLine === __('mail.already_registered.subject'));
});

it('silently drops honeypot submissions', function (): void {
    Livewire::test(RegisterContributor::class)
        ->fillForm(($this->form)([RegisterContributor::HONEYPOT => 'https://spam.example']))
        ->call('register')
        ->assertRedirect(Filament::getLoginUrl());

    expect(User::query()->count())->toBe(0);
    Mail::assertNothingQueued();
});

it('requires the captcha and the terms', function (array $overrides, string $field): void {
    Livewire::test(RegisterContributor::class)
        ->fillForm(($this->form)($overrides))
        ->call('register')
        ->assertHasFormErrors([$field]);

    expect(User::query()->count())->toBe(0);
})->with([
    'captcha failed' => [['captcha_token' => 'bot'], 'captcha_token'],
    'terms not accepted' => [['terms' => false], 'terms'],
    'short password' => [['password' => 'short', 'passwordConfirmation' => 'short'], 'password'],
]);

it('verifies the email from the signed link and tells the editors (FR-CON-02, 11)', function (): void {
    $editor = AdminFixtures::user(Role::Editor);
    Livewire::test(RegisterContributor::class)->fillForm(($this->form)())->call('register');
    $user = User::query()->where('email', 'amna@example.com')->sole();
    $url = URL::temporarySignedRoute(ContributorMailer::VERIFY_ROUTE, now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

    $this->get($url)->assertRedirect('/writer/login');

    expect($user->refresh()->status)->toBe('pending_approval')
        ->and($user->email_verified_at)->not->toBeNull();
    Mail::assertQueued(ElafMail::class, static fn (ElafMail $mail): bool => $mail->hasTo($editor->email)
        && $mail->subjectLine === __('mail.new_registration.subject', ['name' => 'آمنة الطيب']));
});

it('refuses tampered or unsigned verification links', function (): void {
    Livewire::test(RegisterContributor::class)->fillForm(($this->form)())->call('register');
    $user = User::query()->where('email', 'amna@example.com')->sole();

    $this->get(URL::temporarySignedRoute(ContributorMailer::VERIFY_ROUTE, now()->addHour(), ['id' => $user->id, 'hash' => sha1('x')]))->assertForbidden();
    $this->get("/writer/verify-email/{$user->id}/".sha1($user->email))->assertForbidden();
    $this->get(URL::temporarySignedRoute(ContributorMailer::VERIFY_ROUTE, now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]))->assertForbidden();

    expect($user->refresh()->status)->toBe('pending_email');
});
