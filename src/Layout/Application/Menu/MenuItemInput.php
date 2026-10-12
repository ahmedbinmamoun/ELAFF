<?php

declare(strict_types=1);

namespace Elaf\Layout\Application\Menu;

/**
 * One menu row as the form sends it. Children may not have children
 * (one level of nesting, FR-NAV-01).
 */
final readonly class MenuItemInput
{
    /**
     * @param  list<MenuItemInput>  $children
     */
    public function __construct(
        public string $label,
        public string $type,
        public ?int $targetId = null,
        public ?string $url = null,
        public ?string $icon = null,
        public bool $isVisible = true,
        public array $children = [],
    ) {}
}
