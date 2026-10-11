<?php

declare(strict_types=1);

namespace Tests\Support\Contract;

use DateTimeInterface;
use Elaf\Content\Domain\Article\Article;

/**
 * Every observable property of an article, with times as UNIX timestamps
 * so the same instant compares equal across timezones.
 */
final class ArticleState
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Article $article): array
    {
        $time = static fn (?DateTimeInterface $t): ?int => $t?->getTimestamp();

        return [
            'id' => $article->id()->value,
            'origin' => $article->origin(),
            'created_by' => $article->createdBy()->value,
            'created_at' => $time($article->createdAt()),
            'updated_by' => $article->updatedBy()->value,
            'content' => $article->content()->toArray(),
            'status' => $article->status(),
            'published_at' => $time($article->publishedAt()),
            'scheduled_for' => $time($article->scheduledFor()),
            'submitted_at' => $time($article->submittedAt()),
            'reviewed_by' => $article->reviewedBy()?->value,
            'return_note' => $article->returnNote(),
            'breaking' => $article->isBreaking(),
            'breaking_until' => $time($article->breakingUntil()),
            'featured' => $article->isFeatured(),
            'homepage_priority' => $article->homepagePriority(),
            'issue' => $article->issue() === null ? null : [$article->issue()->issueId->value, $article->issue()->page],
        ];
    }
}
