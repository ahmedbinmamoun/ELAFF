<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

interface TaxonomyQueries
{
    /**
     * Visible categories in editorial order (navigation, homepage sections).
     *
     * @return list<CategoryRef>
     */
    public function visibleCategories(): array;

    public function category(string $slug): ?CategoryPage;

    public function author(string $slug): ?AuthorPage;

    public function tag(string $slug): ?TagRef;

    /**
     * The series with its published parts in order (FR-TAX-03).
     */
    public function series(string $slug): ?SeriesPage;
}
