<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage;

use Illuminate\Contracts\View\View;

/**
 * Renders the configured block list into regions (top, main, aside, bottom).
 * Unknown types and empty blocks are skipped.
 */
final readonly class HomepageLayout
{
    public const REGIONS = ['top', 'main', 'aside', 'bottom'];

    public function __construct(private HomepageBlockRegistry $registry) {}

    /**
     * @param  list<array{type?: mixed, region?: mixed, options?: mixed}>  $blocks
     * @return array<string, list<View>>
     */
    public function render(array $blocks): array
    {
        $regions = array_fill_keys(self::REGIONS, []);
        $context = new HomepageContext;

        foreach ($blocks as $block) {
            $renderer = is_string($block['type'] ?? null) ? $this->registry->get($block['type']) : null;
            $region = in_array($block['region'] ?? null, self::REGIONS, true) ? $block['region'] : 'main';
            $options = is_array($block['options'] ?? null) ? $block['options'] : [];

            $view = $renderer?->render(array_filter($options, is_string(...), ARRAY_FILTER_USE_KEY), $context);

            if ($view !== null) {
                $regions[$region][] = $view;
            }
        }

        return $regions;
    }
}
