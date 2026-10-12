<?php

declare(strict_types=1);

namespace Elaf\Publishing\Infrastructure\Persistence;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $number
 * @property CarbonImmutable $published_on
 * @property string|null $title
 * @property string $pdf_path
 * @property int $pdf_size
 * @property string $pdf_checksum
 * @property string|null $cover_path
 * @property string|null $cover_thumb_path
 * @property string|null $social_image_path
 * @property int|null $page_count
 * @property string $processing_status
 * @property string|null $processing_error
 * @property CarbonImmutable|null $processed_at
 * @property int $downloads_count
 * @property CarbonImmutable|null $created_at
 */
final class IssueModel extends Model
{
    protected $table = 'issues';

    protected $guarded = [];

    public $incrementing = false;

    /**
     * @return HasMany<IssuePageModel, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(IssuePageModel::class, 'issue_id')->orderBy('page_number');
    }

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'published_on' => 'immutable_date',
            'pdf_size' => 'integer',
            'page_count' => 'integer',
            'processed_at' => 'immutable_datetime',
            'downloads_count' => 'integer',
            'created_at' => 'immutable_datetime',
        ];
    }
}
