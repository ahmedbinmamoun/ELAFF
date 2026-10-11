<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Eloquent;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Elaf\Content\Application\Article\ArticleCounts;
use Elaf\Content\Application\Article\DueScheduledArticles;
use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleRepository;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Article\Exception\ArticleNotFound;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Identity\Domain\UserId;
use Illuminate\Database\ConnectionInterface;

final readonly class EloquentArticleRepository implements ArticleCounts, ArticleRepository, DueScheduledArticles
{
    public function __construct(
        private ConnectionInterface $db,
        private ArticleMapper $mapper,
    ) {}

    public function nextId(): ArticleId
    {
        return ArticleId::fromInt((int) $this->db->table('article_id_sequence')->insertGetId([]));
    }

    public function get(ArticleId $id): Article
    {
        $model = ArticleModel::query()->with(['authors', 'tags'])->find($id->value);

        if ($model === null) {
            throw ArticleNotFound::withId($id);
        }

        return $this->mapper->toDomain($model);
    }

    public function save(Article $article): void
    {
        $this->db->transaction(function () use ($article): void {
            $model = ArticleModel::query()->find($article->id()->value) ?? new ArticleModel;

            if (! $model->exists) {
                $model->id = $article->id()->value;
                $model->created_at = CarbonImmutable::instance(LocalTime::toColumn($article->createdAt()) ?? $article->createdAt());
            }

            $model->fill($this->mapper->toColumns($article))->save();

            $authors = [];
            foreach ($article->content()->authorIds as $position => $authorId) {
                $authors[$authorId->value] = ['position' => $position];
            }

            $model->authors()->sync($authors);
            $model->tags()->sync(array_map(static fn (TagId $id): int => $id->value, $article->content()->tagIds));
        });
    }

    public function dueAt(DateTimeImmutable $now): array
    {
        return array_values(ArticleModel::query()
            ->where('status', ArticleStatus::Scheduled->value)
            ->where('scheduled_for', '<=', LocalTime::format($now))
            ->orderBy('scheduled_for')
            ->orderBy('id')
            ->get(['id'])
            ->map(static fn (ArticleModel $model): ArticleId => ArticleId::fromInt($model->id))
            ->all());
    }

    public function inReviewBy(UserId $writer): int
    {
        return ArticleModel::query()
            ->where('created_by', $writer->value)
            ->where('status', ArticleStatus::InReview->value)
            ->count();
    }

    public function publishedBy(UserId $writer): array
    {
        return array_values(ArticleModel::query()
            ->where('created_by', $writer->value)
            ->where('status', ArticleStatus::Published->value)
            ->orderBy('id')
            ->get(['id'])
            ->map(static fn (ArticleModel $model): ArticleId => ArticleId::fromInt($model->id))
            ->all());
    }
}
