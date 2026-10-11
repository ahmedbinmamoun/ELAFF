<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Audit;

use Elaf\Content\Domain\Article\Events\ArticleEvent;
use Elaf\Content\Infrastructure\Persistence\Eloquent\LocalTime;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Spatie\Activitylog\ActivityLogger;

/**
 * Writes every article state change and edit to the audit log with actor,
 * time and details/diff (FR-ART-08). Runs after the transaction commits.
 */
final readonly class RecordArticleActivity
{
    public const LOG_NAME = 'articles';

    public function __construct(private ActivityLogger $logger) {}

    public function handle(ArticleEvent $event): void
    {
        $name = self::eventName($event);
        $subject = (new ArticleModel)->forceFill(['id' => $event->articleId->value]);

        $this->logger
            ->useLog(self::LOG_NAME)
            ->performedOn($subject)
            ->event($name)
            ->withProperties($event->details())
            ->createdAt(LocalTime::toColumn($event->at) ?? $event->at)
            ->causedBy($event->actorId->value)
            ->log($name);
    }

    /**
     * ArticleReturnedToWriter → "returned_to_writer".
     */
    public static function eventName(ArticleEvent $event): string
    {
        $short = substr((string) strrchr('\\'.$event::class, '\\'), 1);
        $short = (string) preg_replace('/^Article/', '', $short);

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $short));
    }
}
