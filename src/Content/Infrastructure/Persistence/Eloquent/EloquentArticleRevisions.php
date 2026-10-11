<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Elaf\Content\Application\Article\Revision\ArticleRevision;
use Elaf\Content\Application\Article\Revision\ArticleRevisions;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleRevisionModel;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Validation\RecordNotFound;
use LogicException;

final class EloquentArticleRevisions implements ArticleRevisions
{
    public function record(ArticleId $articleId, UserId $userId, ArticleContent $content, DateTimeImmutable $at): void
    {
        ArticleRevisionModel::query()->create([
            'article_id' => $articleId->value,
            'user_id' => $userId->value,
            'snapshot' => $content->toArray(),
            'created_at' => LocalTime::toColumn($at),
        ]);
    }

    public function get(int $revisionId): ArticleRevision
    {
        $model = ArticleRevisionModel::query()->find($revisionId);

        return $model === null ? throw RecordNotFound::of('Article revision', $revisionId) : $this->toDto($model);
    }

    public function forArticle(ArticleId $articleId): array
    {
        return array_values(ArticleRevisionModel::query()
            ->where('article_id', $articleId->value)
            ->orderByDesc('id')
            ->get()
            ->map(fn (ArticleRevisionModel $model): ArticleRevision => $this->toDto($model))
            ->all());
    }

    private function toDto(ArticleRevisionModel $model): ArticleRevision
    {
        return new ArticleRevision(
            $model->id,
            ArticleId::fromInt($model->article_id),
            UserId::fromInt($model->user_id),
            LocalTime::fromColumn($model->created_at) ?? throw new LogicException('Revision without created_at.'),
            ArticleContent::fromArray($model->snapshot),
        );
    }
}
