<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property string $label
 * @property string $type
 * @property int|null $target_id
 * @property string|null $url
 * @property string|null $icon
 * @property int $position
 * @property bool $is_visible
 */
final class MenuItemModel extends Model
{
    protected $table = 'menu_items';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'menu_id' => 'integer',
            'parent_id' => 'integer',
            'target_id' => 'integer',
            'position' => 'integer',
            'is_visible' => 'boolean',
        ];
    }
}
