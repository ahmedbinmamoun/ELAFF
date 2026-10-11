<?php

declare(strict_types=1);

namespace Elaf\Contributors\Infrastructure;

use Elaf\Content\Domain\Article\Events\ArticleApproved;
use Elaf\Content\Domain\Article\Events\ArticlePublished;
use Elaf\Content\Domain\Article\Events\ArticleReturnedToWriter;
use Elaf\Content\Domain\Article\Events\ArticleSubmittedForReview;
use Elaf\Contributors\Application\AuthorProfiles;
use Elaf\Contributors\Application\ContributorAccounts;
use Elaf\Contributors\Application\Events\RegistrationAttemptedForKnownEmail;
use Elaf\Contributors\Domain\ContributorRepository;
use Elaf\Contributors\Domain\Events\ContributorApproved;
use Elaf\Contributors\Domain\Events\ContributorEmailVerified;
use Elaf\Contributors\Domain\Events\ContributorRegistered;
use Elaf\Contributors\Domain\Events\ContributorRejected;
use Elaf\Contributors\Infrastructure\Mail\ContributorMailer;
use Elaf\Contributors\Infrastructure\Persistence\EloquentContributorAccounts;
use Elaf\Contributors\Infrastructure\Persistence\EloquentContributorRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class ContributorsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        ContributorRepository::class => EloquentContributorRepository::class,
        ContributorAccounts::class => EloquentContributorAccounts::class,
        AuthorProfiles::class => ContentAuthorProfiles::class,
    ];

    public function boot(Dispatcher $events): void
    {
        $events->listen(ContributorRegistered::class, [ContributorMailer::class, 'registered']);
        $events->listen(RegistrationAttemptedForKnownEmail::class, [ContributorMailer::class, 'knownEmail']);
        $events->listen(ContributorEmailVerified::class, [ContributorMailer::class, 'emailVerified']);
        $events->listen(ContributorApproved::class, [ContributorMailer::class, 'approved']);
        $events->listen(ContributorRejected::class, [ContributorMailer::class, 'rejected']);

        foreach ([ArticleSubmittedForReview::class, ArticleApproved::class, ArticleReturnedToWriter::class, ArticlePublished::class] as $event) {
            $events->listen($event, [ContributorMailer::class, 'articleEvent']);
        }
    }
}
