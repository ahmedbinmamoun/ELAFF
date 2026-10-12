<?php

declare(strict_types=1);

namespace Elaf\Settings\Infrastructure\Persistence;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $body
 * @property bool $is_published
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property CarbonImmutable|null $updated_at
 */
final class PageModel extends Model
{
    protected $table = 'pages';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'updated_at' => 'immutable_datetime'];
    }
}
