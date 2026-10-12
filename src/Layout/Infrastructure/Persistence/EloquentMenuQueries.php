<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure\Persistence;

use Elaf\Layout\Application\Menu\Query\MenuEntry;
use Elaf\Layout\Application\Menu\Query\MenuQueries;
use Elaf\Layout\Domain\Menu\MenuItemType;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\JoinClause;
use stdClass;

/**
 * One query for all menus; targets joined so hidden categories, unpublished
 * or deleted pages drop out (with their sub-items).
 */
final readonly class EloquentMenuQueries implements MenuQueries
{
    public function __construct(private ConnectionInterface $db) {}

    public function all(): array
    {
        $rows = $this->db->table('menu_items as i')
            ->join('menus as m', 'm.id', '=', 'i.menu_id')
            ->leftJoin('categories as c', static fn (JoinClause $j) => $j->on('c.id', '=', 'i.target_id')->where('i.type', MenuItemType::Category->value))
            ->leftJoin('pages as p', static fn (JoinClause $j) => $j->on('p.id', '=', 'i.target_id')->where('i.type', MenuItemType::Page->value))
            ->where('i.is_visible', true)
            ->orderBy('i.position')
            ->get(['m.key as menu', 'i.id', 'i.parent_id', 'i.label', 'i.type', 'i.url', 'i.icon', 'c.slug as category_slug', 'c.icon as category_icon', 'c.is_visible as category_visible', 'p.slug as page_slug', 'p.is_published as page_published']);

        $top = [];
        $children = [];

        foreach ($rows as $row) {
            $entry = $this->entry($row);

            if ($entry === null || ! is_numeric($row->id) || ! is_string($row->menu)) {
                continue;
            }

            if (is_numeric($row->parent_id)) {
                $children[(int) $row->parent_id][] = $entry;
            } else {
                $top[$row->menu][(int) $row->id] = $entry;
            }
        }

        $menus = [];

        foreach ($top as $menu => $entries) {
            foreach ($entries as $id => $entry) {
                $menus[$menu][] = new MenuEntry($entry->label, $entry->type, $entry->slug, $entry->url, $entry->icon, $children[$id] ?? []);
            }
        }

        return $menus;
    }

    private function entry(stdClass $row): ?MenuEntry
    {
        $type = MenuItemType::tryFrom(is_string($row->type) ? $row->type : '');
        $label = is_string($row->label) ? $row->label : '';
        $icon = is_string($row->icon) ? $row->icon : (is_string($row->category_icon ?? null) ? $row->category_icon : null);

        return match ($type) {
            MenuItemType::Category => (bool) $row->category_visible && is_string($row->category_slug)
                ? new MenuEntry($label, $type, $row->category_slug, null, $icon) : null,
            MenuItemType::Page => (bool) $row->page_published && is_string($row->page_slug)
                ? new MenuEntry($label, $type, $row->page_slug, null, $icon) : null,
            MenuItemType::Issues => new MenuEntry($label, $type, null, null, $icon),
            MenuItemType::Url => is_string($row->url) ? new MenuEntry($label, $type, null, $row->url, $icon) : null,
            null => null,
        };
    }
}
