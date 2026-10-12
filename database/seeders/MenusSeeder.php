<?php

declare(strict_types=1);

namespace Database\Seeders;

use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Layout\Application\Menu\MenuItemData;
use Elaf\Layout\Application\Menu\MenuRepository;
use Elaf\Layout\Domain\Menu\MenuItemType;
use Elaf\Layout\Domain\Menu\MenuKey;
use Elaf\Layout\Infrastructure\Persistence\MenuModel;
use Elaf\Settings\Infrastructure\Persistence\PageModel;
use Illuminate\Database\Seeder;

/**
 * The three menus (FR-NAV-01). A menu is filled only while it is empty, so
 * editors' changes survive re-runs: header and sections from the visible
 * sections, footer from the static pages and the issues archive.
 */
final class MenusSeeder extends Seeder
{
    public function __construct(private readonly MenuRepository $menus) {}

    public function run(): void
    {
        foreach (MenuKey::cases() as $key) {
            MenuModel::query()->firstOrCreate(['key' => $key->value]);

            if ($this->menus->items($key) === []) {
                $this->menus->replace($key, $key === MenuKey::Footer ? $this->footer() : $this->sections());
            }
        }
    }

    /**
     * @return list<MenuItemData>
     */
    private function sections(): array
    {
        return array_values(CategoryModel::query()->where('is_visible', true)->whereNull('parent_id')->orderBy('sort_order')->get()
            ->map(static fn (CategoryModel $c): MenuItemData => new MenuItemData($c->name, MenuItemType::Category, $c->id, null, null, true))
            ->all());
    }

    /**
     * @return list<MenuItemData>
     */
    private function footer(): array
    {
        $pages = array_values(PageModel::query()->orderBy('id')->get()
            ->map(static fn (PageModel $p): MenuItemData => new MenuItemData($p->title, MenuItemType::Page, $p->id, null, null, true))
            ->all());

        return [...$pages, new MenuItemData((string) __('public.issues_page.title'), MenuItemType::Issues, null, null, 'newspaper', true)];
    }
}
