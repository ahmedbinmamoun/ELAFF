<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Menus;

use Elaf\Content\Application\Query\TaxonomyQueries;
use Elaf\Layout\Application\Menu\Query\MenuEntry;
use Elaf\Layout\Application\Menu\Query\MenuQueries;
use Elaf\Layout\Domain\Menu\MenuItemType;
use Elaf\Layout\Domain\Menu\MenuKey;
use Illuminate\Http\Request;

/**
 * The public menus as links (FR-NAV-01). Until an editor saves the header
 * or sections menu, the visible categories stand in, so a fresh
 * installation still has navigation.
 */
final readonly class PublicMenus
{
    public function __construct(
        private MenuQueries $menus,
        private TaxonomyQueries $taxonomy,
        private MenuLinkRegistry $links,
        private Request $request,
    ) {}

    /**
     * @return array<string, list<MenuLink>> by menu key
     */
    public function all(): array
    {
        $menus = $this->menus->all();
        $categories = null;
        $result = [];

        foreach (MenuKey::cases() as $key) {
            $entries = $menus[$key->value] ?? [];

            if ($entries === [] && $key !== MenuKey::Footer) {
                $entries = $categories ??= $this->categories();
            }

            $result[$key->value] = array_map(fn (MenuEntry $e): MenuLink => $this->links->link($e, $this->request), $entries);
        }

        return $result;
    }

    /**
     * @return list<MenuEntry>
     */
    private function categories(): array
    {
        $entries = [];

        foreach ($this->taxonomy->visibleCategories() as $category) {
            $entries[] = new MenuEntry($category->name, MenuItemType::Category, $category->slug, null, $category->icon);
        }

        return $entries;
    }
}
