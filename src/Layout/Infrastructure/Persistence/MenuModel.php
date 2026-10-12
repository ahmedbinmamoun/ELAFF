<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $key
 */
final class MenuModel extends Model
{
    protected $table = 'menus';

    protected $guarded = [];

    /**
     * @return HasMany<MenuItemModel, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItemModel::class, 'menu_id')->orderBy('position');
    }
}
