<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $type
 * @property string $region
 * @property array<string, mixed>|null $config
 * @property int $position
 * @property bool $is_active
 */
final class HomepageBlockModel extends Model
{
    protected $table = 'homepage_blocks';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['config' => 'array', 'position' => 'integer', 'is_active' => 'boolean'];
    }
}
