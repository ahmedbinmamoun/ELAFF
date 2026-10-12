<?php

declare(strict_types=1);

namespace Elaf\Shared\Presentation\Filament;

use Elaf\Content\Domain\Article\Exception\ArticleNotPublishable;
use Elaf\Content\Domain\Article\Exception\ArticleWorkflowViolation;
use Elaf\Content\Domain\Article\Exception\IllegalArticleTransition;
use Elaf\Contributors\Domain\Exception\ContributorRuleViolation;
use Elaf\Contributors\Domain\Exception\IllegalContributorTransition;
use Elaf\Engagement\Domain\Letter\Exception\LetterWorkflowViolation;
use Elaf\Publishing\Domain\Issue\Exception\IllegalIssueTransition;
use Elaf\Publishing\Domain\Issue\Exception\InvalidIssue;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Application\Validation\RecordNotFound;
use Elaf\Shared\Domain\Exception\DomainException;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Translates use-case failures for the admin: field errors on forms, a
 * notification for actions. Pure mapping, no rules.
 */
final class UseCaseErrors
{
    /**
     * Runs $work from a form page. InvalidInput becomes a validation error on
     * "data.<field>" (renamed through $fields); other failures a notification.
     *
     * @template T
     *
     * @param  callable(): T  $work
     * @param  array<string, string>  $fields  use-case field => form field
     * @return T
     */
    public static function forForm(callable $work, array $fields = []): mixed
    {
        try {
            return $work();
        } catch (InvalidInput $e) {
            throw ValidationException::withMessages(['data.'.($fields[$e->field] ?? $e->field) => self::message($e)]);
        } catch (NotAuthorized|RecordNotFound|DomainException $e) {
            self::notify($e);

            throw new Halt;
        }
    }

    /**
     * Runs $work from an action. Returns false (after notifying) on failure.
     */
    public static function forAction(callable $work): bool
    {
        try {
            $work();

            return true;
        } catch (InvalidInput|NotAuthorized|RecordNotFound|DomainException $e) {
            self::notify($e);

            return false;
        }
    }

    public static function message(Throwable $e): string
    {
        return match (true) {
            $e instanceof InvalidInput => self::translate($e->reason, $e->params),
            $e instanceof ArticleWorkflowViolation => self::translate($e->reason),
            $e instanceof IllegalArticleTransition => self::translate('illegal_transition', [
                'from' => __('admin.status.'.$e->from->value),
                'to' => __('admin.status.'.$e->to->value),
            ]),
            $e instanceof ArticleNotPublishable => self::translate('not_publishable', [
                'missing' => implode('، ', array_map(static fn (string $m): string => self::translate('missing.'.$m), $e->missing)),
            ]),
            $e instanceof ContributorRuleViolation => self::translate($e->reason),
            $e instanceof IllegalContributorTransition => self::translate('illegal_contributor_transition'),
            $e instanceof IllegalIssueTransition => self::translate('illegal_issue_transition'),
            $e instanceof LetterWorkflowViolation => self::translate($e->reason),
            $e instanceof InvalidIssue => self::translate($e->reason),
            $e instanceof NotAuthorized => self::translate('not_authorized'),
            $e instanceof RecordNotFound => self::translate('not_found'),
            default => self::translate('failed'),
        };
    }

    private static function notify(Throwable $e): void
    {
        Notification::make()->danger()->title(self::message($e))->send();
    }

    /**
     * @param  array<string, string|int>  $params
     */
    private static function translate(string $key, array $params = []): string
    {
        $message = __('errors.'.$key, $params);

        return is_string($message) && $message !== 'errors.'.$key ? $message : (string) __('errors.failed');
    }
}
