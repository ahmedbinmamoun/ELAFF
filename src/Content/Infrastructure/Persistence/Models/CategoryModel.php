<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_visible
 * @property bool $is_open_to_contributors
 * @property string|null $seo_title
 * @property string|null $seo_description
 */
final class CategoryModel extends Model
{
    protected $table = 'categories';

    protected $guarded = [];

    /**
     * @return BelongsTo<CategoryModel, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<ArticleModel, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(ArticleModel::class, 'category_id');
    }

    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
            'is_open_to_contributors' => 'boolean',
        ];
    }
}
