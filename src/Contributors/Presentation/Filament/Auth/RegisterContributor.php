<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Filament\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Elaf\Contributors\Application\Register\RegisterContributorCommand;
use Elaf\Contributors\Application\Register\RegisterContributorHandler;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Elaf\Shared\Presentation\PublicMedia;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Config\Repository;

/**
 * FR-CON-01 at /writer/register (/writers/register redirects here).
 * Honeypot + rate limit + Turnstile; no auto-login and the same answer
 * whether the email is new or known.
 */
final class RegisterContributor extends Register
{
    public const HONEYPOT = 'website';

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(3);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (FormData::text($data, self::HONEYPOT) === null) {
            UseCaseErrors::forForm(static fn () => app(RegisterContributorHandler::class)->handle(new RegisterContributorCommand(
                name: FormData::string($data, 'name'),
                email: FormData::string($data, 'email'),
                password: FormData::string($data, 'password'),
                bio: FormData::string($data, 'bio'),
                acceptedTerms: FormData::bool($data, 'terms'),
                phone: FormData::text($data, 'phone'),
                photoPath: FormData::text($data, 'photo_path'),
                captchaToken: FormData::text($data, 'captcha_token'),
                ip: request()->ip(),
            )), ['captcha' => 'captcha_token', 'photo' => 'photo_path']);
        }

        Notification::make()
            ->success()
            ->title(__('contributors.register.received'))
            ->body(__('contributors.register.check_email'))
            ->persistent()
            ->send();

        $this->redirect(Filament::getLoginUrl());

        return null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('contributors.fields.name'))->required()->maxLength(120)->autofocus(),
            TextInput::make('email')->label(__('contributors.fields.email'))->email()->required()->maxLength(255),
            TextInput::make('password')
                ->label(__('contributors.fields.password'))
                ->password()
                ->revealable()
                ->required()
                ->minLength(10)
                ->same('passwordConfirmation'),
            TextInput::make('passwordConfirmation')->label(__('contributors.fields.password_confirmation'))->password()->required()->dehydrated(false),
            Textarea::make('bio')->label(__('contributors.fields.bio'))->required()->rows(3)->maxLength(1000),
            TextInput::make('phone')->label(__('contributors.fields.phone'))->tel()->maxLength(30),
            FileUpload::make('photo_path')
                ->label(__('contributors.fields.photo'))
                ->image()
                ->avatar()
                ->disk(PublicMedia::DISK)
                ->directory('contributors')
                ->maxSize(2048),
            Checkbox::make('terms')
                ->label(__('contributors.fields.terms'))
                ->helperText(__('contributors.terms_summary'))
                ->accepted(),
            TextInput::make(self::HONEYPOT)
                ->label('Website')
                ->extraAttributes(['style' => 'position:absolute;left:-9999px;', 'aria-hidden' => 'true'])
                ->extraInputAttributes(['tabindex' => '-1', 'autocomplete' => 'off'])
                ->hiddenLabel(),
            ViewField::make('captcha_token')
                ->view('filament.forms.turnstile')
                ->viewData(['siteKey' => app(Repository::class)->get('elaf.turnstile.site_key')]),
        ]);
    }
}
