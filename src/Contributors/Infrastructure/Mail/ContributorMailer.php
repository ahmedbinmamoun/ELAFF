<?php

declare(strict_types=1);

namespace Elaf\Contributors\Infrastructure\Mail;

use App\Models\User;
use Elaf\Content\Application\ContentPermission;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\Events\ArticleApproved;
use Elaf\Content\Domain\Article\Events\ArticleEvent;
use Elaf\Content\Domain\Article\Events\ArticlePublished;
use Elaf\Content\Domain\Article\Events\ArticleReturnedToWriter;
use Elaf\Content\Domain\Article\Events\ArticleSubmittedForReview;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Contributors\Application\ContributorPermission;
use Elaf\Contributors\Application\Events\RegistrationAttemptedForKnownEmail;
use Elaf\Contributors\Domain\Events\ContributorApproved;
use Elaf\Contributors\Domain\Events\ContributorEmailVerified;
use Elaf\Contributors\Domain\Events\ContributorRegistered;
use Elaf\Contributors\Domain\Events\ContributorRejected;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Facades\URL;

/**
 * FR-CON-11 emails, sent (queued) after the change is committed.
 */
final readonly class ContributorMailer
{
    public const VERIFY_ROUTE = 'writer.verify-email';

    public const VERIFY_HOURS = 48;

    public function __construct(
        private Mailer $mail,
        private UrlGenerator $urls,
    ) {}

    public function registered(ContributorRegistered $event): void
    {
        $user = User::query()->find($event->contributorId->value);

        if ($user === null) {
            return;
        }

        $url = URL::temporarySignedRoute(self::VERIFY_ROUTE, now()->addHours(self::VERIFY_HOURS), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->send($user->email, 'verify', [], [__('mail.greeting', ['name' => $user->name]), __('mail.verify.body'), __('mail.verify.after')], $url);
    }

    public function knownEmail(RegistrationAttemptedForKnownEmail $event): void
    {
        $this->send($event->email, 'already_registered', [], [__('mail.already_registered.body')], $this->urls->to('/writer/login'));
    }

    public function emailVerified(ContributorEmailVerified $event): void
    {
        $user = User::query()->find($event->contributorId->value);

        if ($user === null || $user->status !== 'pending_approval') {
            return;
        }

        foreach ($this->staffWith(ContributorPermission::ManageContributors->value) as $editor) {
            $this->send($editor->email, 'new_registration', ['name' => $user->name], [
                __('mail.new_registration.body', ['name' => $user->name, 'email' => $user->email]),
            ], $this->urls->to('/admin/contributors'));
        }
    }

    public function approved(ContributorApproved $event): void
    {
        $user = User::query()->find($event->contributorId->value);

        if ($user !== null) {
            $this->send($user->email, 'account_approved', [], [__('mail.greeting', ['name' => $user->name]), __('mail.account_approved.body')], $this->urls->to('/writer'));
        }
    }

    public function rejected(ContributorRejected $event): void
    {
        $user = User::query()->find($event->contributorId->value);

        if ($user !== null) {
            $this->send($user->email, 'account_rejected', [], [__('mail.greeting', ['name' => $user->name]), __('mail.account_rejected.body'), $event->reason]);
        }
    }

    public function articleEvent(ArticleEvent $event): void
    {
        $article = ArticleModel::query()->with('writer')->find($event->articleId->value);

        if ($article === null || $article->origin !== ArticleOrigin::Contributor->value || $article->writer === null) {
            return;
        }

        $writer = $article->writer;
        $params = ['title' => $article->title, 'name' => $writer->name];

        match (true) {
            $event instanceof ArticleSubmittedForReview => $this->notifyReviewers($article, $params),
            $event instanceof ArticleApproved => $this->send($writer->email, 'article_approved', $params, [__('mail.article_approved.body', $params)], $this->urls->to('/writer/articles')),
            $event instanceof ArticleReturnedToWriter => $this->send($writer->email, 'article_returned', $params, [__('mail.article_returned.body', $params), $event->note], $this->urls->to("/writer/articles/{$article->id}/edit")),
            $event instanceof ArticlePublished => $this->send($writer->email, 'article_published', $params, [__('mail.article_published.body', $params)], $this->urls->to('/writer/articles')),
            default => null,
        };
    }

    /**
     * @param  array<string, string>  $params
     */
    private function notifyReviewers(ArticleModel $article, array $params): void
    {
        foreach ($this->staffWith(ContentPermission::ReviewArticles->value) as $editor) {
            $this->send($editor->email, 'submission', $params, [__('mail.submission.body', $params)], $this->urls->to("/admin/articles/{$article->id}"));
        }
    }

    /**
     * @return iterable<User>
     */
    private function staffWith(string $permission): iterable
    {
        return User::permission($permission)->where('type', 'staff')->where('status', 'active')->get();
    }

    /**
     * @param  array<string, string>  $subjectParams
     * @param  list<mixed>  $lines
     */
    private function send(string $to, string $key, array $subjectParams, array $lines, ?string $url = null): void
    {
        $label = __("mail.{$key}.action");

        $this->mail->to($to)->queue(new ElafMail(
            subjectLine: (string) __("mail.{$key}.subject", $subjectParams),
            heading: (string) __("mail.{$key}.heading"),
            lines: array_map(static fn (mixed $l): string => is_string($l) ? $l : '', $lines),
            actionUrl: $url,
            actionLabel: $url === null || ! is_string($label) ? null : $label,
        ));
    }
}
