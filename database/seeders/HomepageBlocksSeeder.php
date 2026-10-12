<?php

declare(strict_types=1);

namespace Database\Seeders;

use Elaf\Layout\Application\Homepage\HomepageBlockData;
use Elaf\Layout\Application\Homepage\HomepageBlockRepository;
use Elaf\Layout\Domain\Homepage\BlockRegion;
use Illuminate\Database\Seeder;

/**
 * Stores the default composition (config/homepage.php) as editable blocks,
 * once: an existing list is never touched.
 */
final class HomepageBlocksSeeder extends Seeder
{
    public function __construct(private readonly HomepageBlockRepository $blocks) {}

    public function run(): void
    {
        if ($this->blocks->all() !== []) {
            return;
        }

        $defaults = config('homepage.blocks', []);

        foreach (is_array($defaults) ? $defaults : [] as $block) {
            if (! is_array($block) || ! is_string($block['type'] ?? null)) {
                continue;
            }

            $options = [];

            foreach (is_array($block['options'] ?? null) ? $block['options'] : [] as $key => $value) {
                if (is_string($key) && (is_string($value) || is_int($value) || is_bool($value))) {
                    $options[$key] = $value;
                }
            }

            $this->blocks->add(new HomepageBlockData(
                $block['type'],
                BlockRegion::tryFrom(is_string($block['region'] ?? null) ? $block['region'] : '') ?? BlockRegion::Main,
                $options,
            ));
        }
    }
}
