<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Http;

use App\Models\User;
use Elaf\Contributors\Application\VerifyEmail\VerifyContributorEmailCommand;
use Elaf\Contributors\Application\VerifyEmail\VerifyContributorEmailHandler;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;

/**
 * FR-CON-02: the signed link from the verification email. No sign-in needed;
 * the signature and the email hash prove the address.
 */
final readonly class VerifyEmailController
{
    public function __construct(private VerifyContributorEmailHandler $handler) {}

    public function __invoke(int $id, string $hash): RedirectResponse
    {
        $user = User::query()->where('type', 'contributor')->find($id);

        abort_if($user === null || ! hash_equals(sha1($user->email), $hash), 403);

        $this->handler->handle(new VerifyContributorEmailCommand($user->id));

        Notification::make()->success()->title(__('contributors.register.verified'))->body(__('contributors.register.verified_body'))->persistent()->send();

        return redirect('/writer/login');
    }
}
