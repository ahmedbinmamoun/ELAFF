<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Persistence shape of the Article aggregate. Written only by
 * EloquentArticleRepository; Filament may read it (PRD §5.2).
 *
 * @property int $id
 * @property string $origin
 * @property int $category_id
 * @property int|null $series_id
 * @property int|null $series_part
 * @property int|null $issue_id
 * @property int|null $issue_page
 * @property string|null $kicker
 * @property string $title
 * @property string|null $subtitle
 * @property string $slug
 * @property string|null $dateline
 * @property string|null $excerpt
 * @property string $body
 * @property string|null $featured_image_path
 * @property string|null $featured_image_caption
 * @property string|null $featured_image_credit
 * @property string $source_name
 * @property string|null $source_url
 * @property string $status
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $scheduled_for
 * @property bool $is_breaking
 * @property CarbonImmutable|null $breaking_until
 * @property bool $is_featured
 * @property int $homepage_priority
 * @property int $views_count
 * @property int $reading_minutes
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property string|null $canonical_url
 * @property int $created_by
 * @property int $updated_by
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $submitted_at
 * @property string|null $return_note
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Collection<int, AuthorModel> $authors
 * @property-read Collection<int, TagModel> $tags
 */
final class ArticleModel extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $table = 'articles';

    protected $guarded = [];

    /**
     * @return BelongsTo<CategoryModel, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }

    /**
     * Byline order.
     *
     * @return BelongsToMany<AuthorModel, $this>
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(AuthorModel::class, 'article_author', 'article_id', 'author_id')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /**
     * @return BelongsToMany<TagModel, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(TagModel::class, 'article_tag', 'article_id', 'tag_id');
    }

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'series_id' => 'integer',
            'series_part' => 'integer',
            'issue_id' => 'integer',
            'issue_page' => 'integer',
            'published_at' => 'immutable_datetime',
            'scheduled_for' => 'immutable_datetime',
            'breaking_until' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'is_breaking' => 'boolean',
            'is_featured' => 'boolean',
            'homepage_priority' => 'integer',
            'views_count' => 'integer',
            'reading_minutes' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'reviewed_by' => 'integer',
        ];
    }
}
