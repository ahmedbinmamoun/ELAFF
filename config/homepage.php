<?php

declare(strict_types=1);

/*
 * Homepage composition (FR-HOME-01/02): an ordered list of blocks. Each "type"
 * is a renderer registered in HomepageBlockRegistry; adding a type means one
 * new class and one registration, no change elsewhere (FR-HOME-03).
 *
 * region: top (full width) | main (centre column) | aside (end column on
 * desktop) | bottom (full width). On mobile all regions stack in that order.
 *
 * Phase 5 moves this list into the homepage_blocks table, editable in the admin.
 */
return [
    'blocks' => [
        ['type' => 'hero', 'region' => 'top', 'options' => ['secondary' => 3]],
        ['type' => 'latest_articles', 'region' => 'main', 'options' => ['featured' => 2, 'strip' => 8]],
        ['type' => 'most_read', 'region' => 'aside', 'options' => ['limit' => 5, 'days' => 30]],
        ['type' => 'current_issue', 'region' => 'aside', 'options' => []],
        ['type' => 'columnists', 'region' => 'bottom', 'options' => ['limit' => 6]],
        ['type' => 'category_section', 'region' => 'bottom', 'options' => ['categories' => 4, 'count' => 4]],
    ],
];
