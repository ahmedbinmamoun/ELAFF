<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string $slug
 * @property string|null $title
 * @property string|null $bio
 * @property string|null $photo_path
 * @property bool $is_columnist
 * @property array<string, string>|null $social_links
 */
final class AuthorModel extends Model
{
    use SoftDeletes;

    protected $table = 'authors';

    protected $guarded = [];

    /**
     * @return BelongsToMany<ArticleModel, $this>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(ArticleModel::class, 'article_author', 'author_id', 'article_id');
    }

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
            'user_id' => 'integer',
            'is_columnist' => 'boolean',
            'social_links' => 'array',
        ];
    }
}
