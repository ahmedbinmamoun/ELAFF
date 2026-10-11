<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage\Blocks;

use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Content\Application\Query\CategoryRef;
use Elaf\Content\Application\Query\TaxonomyQueries;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRenderer;
use Elaf\Layout\Presentation\Homepage\HomepageContext;
use Illuminate\Contracts\View\View;

/**
 * Sections by category (FR-HOME-02 "category_section"). Options: "category"
 * (a slug, one section) or "categories" (the first N visible ones), "count".
 * All sections load in one query.
 */
final readonly class CategorySectionBlock implements HomepageBlockRenderer
{
    public function __construct(
        private ArticleQueries $articles,
        private TaxonomyQueries $taxonomy,
    ) {}

    public function type(): string
    {
        return 'category_section';
    }

    public function render(array $options, HomepageContext $context): ?View
    {
        $visible = $this->taxonomy->visibleCategories();
        $slug = $options['category'] ?? null;
        $chosen = is_string($slug)
            ? array_values(array_filter($visible, static fn (CategoryRef $c): bool => $c->slug === $slug))
            : array_slice($visible, 0, BlockOptions::int($options, 'categories', 4, 12));

        $sections = $this->articles->categorySections(
            array_map(static fn (CategoryRef $c): int => $c->id, $chosen),
            BlockOptions::int($options, 'count', 4, 8),
        );

        return $sections === [] ? null : view('homepage.blocks.category-sections', ['sections' => $sections]);
    }
}
