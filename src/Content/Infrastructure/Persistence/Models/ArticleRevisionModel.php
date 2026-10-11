<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $article_id
 * @property int $user_id
 * @property array<mixed> $snapshot
 * @property CarbonImmutable $created_at
 */
final class ArticleRevisionModel extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'article_revisions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'article_id' => 'integer',
            'user_id' => 'integer',
            'snapshot' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
