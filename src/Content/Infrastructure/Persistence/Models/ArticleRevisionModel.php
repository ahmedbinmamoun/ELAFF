<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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
