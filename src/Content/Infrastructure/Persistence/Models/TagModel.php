<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 */
final class TagModel extends Model
{
    protected $table = 'tags';

    protected $guarded = [];

    /**
     * @return BelongsToMany<ArticleModel, $this>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(ArticleModel::class, 'article_tag', 'tag_id', 'article_id');
    }
}
