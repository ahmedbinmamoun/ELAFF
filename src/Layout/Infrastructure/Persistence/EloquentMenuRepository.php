<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure\Persistence;

use Elaf\Layout\Application\Menu\MenuItemData;
use Elaf\Layout\Application\Menu\MenuRepository;
use Elaf\Layout\Domain\Menu\MenuItemType;
use Elaf\Layout\Domain\Menu\MenuKey;
use Illuminate\Database\ConnectionInterface;

final readonly class EloquentMenuRepository implements MenuRepository
{
    public function __construct(private ConnectionInterface $db) {}

    public function items(MenuKey $menu): array
    {
        $rows = MenuItemModel::query()
            ->join('menus', 'menus.id', '=', 'menu_items.menu_id')
            ->where('menus.key', $menu->value)
            ->orderBy('menu_items.position')
            ->get(['menu_items.*']);

        $children = [];

        foreach ($rows as $row) {
            if ($row->parent_id !== null) {
                $children[$row->parent_id][] = $this->data($row, []);
            }
        }

        $items = [];

        foreach ($rows as $row) {
            if ($row->parent_id === null) {
                $items[] = $this->data($row, $children[$row->id] ?? []);
            }
        }

        return $items;
    }

    public function replace(MenuKey $menu, array $items): void
    {
        $this->db->transaction(function () use ($menu, $items): void {
            $model = MenuModel::query()->firstOrCreate(['key' => $menu->value]);
            MenuItemModel::query()->where('menu_id', $model->id)->whereNotNull('parent_id')->delete();
            MenuItemModel::query()->where('menu_id', $model->id)->delete();

            foreach ($items as $position => $item) {
                $parent = $this->insert($model->id, null, $position, $item);

                foreach ($item->children as $childPosition => $child) {
                    $this->insert($model->id, $parent, $childPosition, $child);
                }
            }
        });
    }

    private function insert(int $menuId, ?int $parentId, int $position, MenuItemData $item): int
    {
        return MenuItemModel::query()->create([
            'menu_id' => $menuId,
            'parent_id' => $parentId,
            'label' => $item->label,
            'type' => $item->type->value,
            'target_id' => $item->targetId,
            'url' => $item->url,
            'icon' => $item->icon,
            'position' => $position,
            'is_visible' => $item->isVisible,
        ])->id;
    }

    /**
     * @param  list<MenuItemData>  $children
     */
    private function data(MenuItemModel $row, array $children): MenuItemData
    {
        return new MenuItemData($row->label, MenuItemType::from($row->type), $row->target_id, $row->url, $row->icon, $row->is_visible, $children);
    }
}
