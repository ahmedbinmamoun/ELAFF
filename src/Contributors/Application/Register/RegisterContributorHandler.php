<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\Register;

use Elaf\Contributors\Application\ContributorAccounts;
use Elaf\Contributors\Application\ContributorInput;
use Elaf\Contributors\Application\Events\RegistrationAttemptedForKnownEmail;
use Elaf\Contributors\Domain\Contributor;
use Elaf\Contributors\Domain\ContributorRepository;
use Elaf\Identity\Application\StaffUser\PasswordHasher;
use Elaf\Identity\Application\StaffUser\StaffUserInputValidator;
use Elaf\Shared\Application\Captcha\CaptchaVerifier;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Application\Validation\Fields;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-CON-01. The outcome for a known email is indistinguishable from a new
 * registration: same return, no error; the address owner gets an email.
 */
final readonly class RegisterContributorHandler
{
    public function __construct(
        private ContributorAccounts $accounts,
        private ContributorRepository $contributors,
        private PasswordHasher $hasher,
        private CaptchaVerifier $captcha,
        private Transaction $transaction,
        private EventDispatcher $events,
        private Clock $clock,
    ) {}

    public function handle(RegisterContributorCommand $command): void
    {
        if (! $this->captcha->verify($command->captchaToken, $command->ip)) {
            throw InvalidInput::because('captcha', 'captcha_failed', 'The anti-spam check failed.');
        }

        if (! $command->acceptedTerms) {
            throw InvalidInput::because('terms', 'terms_required', 'The contributor terms must be accepted.');
        }

        $name = ContributorInput::name($command->name);
        $profile = ContributorInput::profile($command->bio, $command->phone, $command->photoPath);
        $email = mb_strtolower(Fields::required('email', $command->email, 255));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidInput::because('email', 'invalid_email', 'The email address is not valid.');
        }

        if (mb_strlen($command->password) < StaffUserInputValidator::MIN_PASSWORD_LENGTH) {
            throw InvalidInput::tooShort('password', StaffUserInputValidator::MIN_PASSWORD_LENGTH);
        }

        if ($this->accounts->emailExists($email)) {
            $this->events->dispatch(new RegistrationAttemptedForKnownEmail($email, $this->clock->now()));

            return;
        }

        $this->transaction->run(function () use ($name, $email, $profile, $command): void {
            $id = $this->accounts->create($name, $email, $this->hasher->hash($command->password));
            $contributor = Contributor::register($id, $name, $profile, $this->clock);
            $this->contributors->save($contributor);
            $this->events->dispatch(...$contributor->releaseEvents());
        });
    }
}
