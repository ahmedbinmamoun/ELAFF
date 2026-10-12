<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure\Persistence;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $title
 * @property string $slot
 * @property string|null $image_path
 * @property string|null $html
 * @property string|null $target_url
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property int $weight
 * @property bool $is_active
 * @property int $clicks_count
 */
final class AdModel extends Model
{
    use SoftDeletes;

    protected $table = 'ads';

    protected $guarded = [];

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'weight' => 'integer',
            'is_active' => 'boolean',
            'clicks_count' => 'integer',
        ];
    }
}
