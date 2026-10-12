<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Menu\SaveMenu;

use Elaf\Layout\Application\LayoutPermission;
use Elaf\Layout\Application\Menu\MenuItemData;
use Elaf\Layout\Application\Menu\MenuItemInput;
use Elaf\Layout\Application\Menu\MenuRepository;
use Elaf\Layout\Application\Menu\MenuTargets;
use Elaf\Layout\Domain\Menu\Events\MenuChanged;
use Elaf\Layout\Domain\Menu\MenuItemType;
use Elaf\Layout\Domain\Menu\MenuKey;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Domain\Text\OptionalText;
use Elaf\Shared\Domain\Time\Clock;

/**
 * FR-NAV-01: a menu is saved as a whole (order, nesting, visibility).
 * Errors name the item by its position ("3" or "3.2") and label.
 */
final readonly class SaveMenuHandler
{
    public const MAX_ITEMS = 40;

    public function __construct(
        private Authorizer $authorizer,
        private MenuRepository $menus,
        private MenuTargets $targets,
        private Transaction $transaction,
        private EventDispatcher $events,
        private Clock $clock,
    ) {}

    public function handle(SaveMenuCommand $command): void
    {
        if (! $this->authorizer->allows(LayoutPermission::ManageMenus->value)) {
            throw NotAuthorized::lacks(LayoutPermission::ManageMenus->value);
        }

        $menu = MenuKey::tryFrom($command->menu) ?? throw InvalidInput::because('menu', 'unknown_menu', 'Unknown menu.');
        $count = count($command->items) + array_sum(array_map(static fn (MenuItemInput $i): int => count($i->children), $command->items));

        if ($count > self::MAX_ITEMS) {
            throw InvalidInput::because('items', 'menu_too_long', 'Too many menu items.', ['max' => self::MAX_ITEMS]);
        }

        $items = [];

        foreach ($command->items as $index => $input) {
            $position = (string) ($index + 1);
            $children = [];

            foreach ($input->children as $childIndex => $child) {
                if ($child->children !== []) {
                    throw InvalidInput::because('items', 'menu_too_deep', 'Menus have one level of sub-items.', ['position' => $position.'.'.($childIndex + 1)]);
                }

                $children[] = $this->item($child, $position.'.'.($childIndex + 1), []);
            }

            $items[] = $this->item($input, $position, $children);
        }

        $this->transaction->run(function () use ($menu, $items, $command): void {
            $this->menus->replace($menu, $items);
            $this->events->dispatch(new MenuChanged($menu, $command->actorId, $this->clock->now()));
        });
    }

    /**
     * @param  list<MenuItemData>  $children
     */
    private function item(MenuItemInput $input, string $position, array $children): MenuItemData
    {
        $fail = static fn (string $reason, string $message): InvalidInput => InvalidInput::because('items', $reason, $message, ['position' => $position]);
        $label = trim($input->label);

        if ($label === '' || mb_strlen($label) > 60) {
            throw $fail('menu_item_label', 'A menu item needs a label of at most 60 characters.');
        }

        $type = MenuItemType::tryFrom($input->type) ?? throw $fail('menu_item_type', 'Unknown menu item type.');
        $exists = match ($type) {
            MenuItemType::Category => $input->targetId !== null && $this->targets->categoryExists($input->targetId),
            MenuItemType::Page => $input->targetId !== null && $this->targets->pageExists($input->targetId),
            MenuItemType::Issues, MenuItemType::Url => true,
        };

        if (! $exists) {
            throw $fail('menu_item_target', 'The linked record does not exist.');
        }

        $url = null;

        if ($type === MenuItemType::Url) {
            $url = OptionalText::normalize($input->url);
            $internal = $url !== null && str_starts_with($url, '/') && ! str_starts_with($url, '//');

            if ($url === null || mb_strlen($url) > 2048 || ! ($internal || OptionalText::isHttpUrl($url))) {
                throw $fail('menu_item_url', 'A link must start with / or http(s)://.');
            }
        }

        $icon = OptionalText::normalize($input->icon);

        if ($icon !== null && preg_match('/^[a-z0-9-]{1,40}$/', $icon) !== 1) {
            throw $fail('invalid_icon', 'The icon key is not valid.');
        }

        return new MenuItemData($label, $type, $type->needsTarget() ? $input->targetId : null, $url, $icon, $input->isVisible, $children);
    }
}
