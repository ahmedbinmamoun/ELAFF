<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Query;

use Elaf\Content\Application\Query\AuthorPage;
use Elaf\Content\Application\Query\AuthorRef;
use Elaf\Content\Application\Query\CategoryPage;
use Elaf\Content\Application\Query\CategoryRef;
use Elaf\Content\Application\Query\SeriesPage;
use Elaf\Content\Application\Query\TagRef;
use Elaf\Content\Application\Query\TaxonomyQueries;
use Illuminate\Database\ConnectionInterface;
use stdClass;

final readonly class EloquentTaxonomyQueries implements TaxonomyQueries
{
    public function __construct(
        private ConnectionInterface $db,
        private ArticleCardHydrator $cards,
    ) {}

    public function visibleCategories(): array
    {
        return array_values($this->db->table('categories')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'icon'])
            ->map(static fn (mixed $r): CategoryRef => self::categoryRef(self::row($r)))
            ->all());
    }

    public function category(string $slug): ?CategoryPage
    {
        $row = $this->db->table('categories')->where('slug', $slug)->where('is_visible', true)->first();

        return $row instanceof stdClass ? new CategoryPage(
            self::categoryRef($row),
            ArticleCardHydrator::nullable($row, 'description'),
            ArticleCardHydrator::nullable($row, 'seo_title'),
            ArticleCardHydrator::nullable($row, 'seo_description'),
        ) : null;
    }

    public function author(string $slug): ?AuthorPage
    {
        $row = $this->db->table('authors')->where('slug', $slug)->whereNull('deleted_at')->first();

        if (! $row instanceof stdClass) {
            return null;
        }

        $links = json_decode(ArticleCardHydrator::string($row, 'social_links') ?: '[]', true);

        return new AuthorPage(
            id: ArticleCardHydrator::int($row, 'id'),
            author: new AuthorRef(
                ArticleCardHydrator::string($row, 'name'),
                ArticleCardHydrator::string($row, 'slug'),
                ArticleCardHydrator::nullable($row, 'title'),
                ArticleCardHydrator::nullable($row, 'photo_path'),
            ),
            bio: ArticleCardHydrator::nullable($row, 'bio'),
            socialLinks: is_array($links) ? array_filter(array_map(static fn (mixed $v): string => is_string($v) ? $v : '', $links), static fn (string $v, int|string $k): bool => $v !== '' && is_string($k), ARRAY_FILTER_USE_BOTH) : [],
            isColumnist: (bool) ArticleCardHydrator::int($row, 'is_columnist'),
        );
    }

    public function tag(string $slug): ?TagRef
    {
        $row = $this->db->table('tags')->where('slug', $slug)->first();

        return $row instanceof stdClass ? new TagRef(ArticleCardHydrator::string($row, 'name'), ArticleCardHydrator::string($row, 'slug')) : null;
    }

    public function series(string $slug): ?SeriesPage
    {
        $row = $this->db->table('series')->where('slug', $slug)->first();

        if (! $row instanceof stdClass) {
            return null;
        }

        $parts = $this->cards->published()
            ->where('a.series_id', ArticleCardHydrator::int($row, 'id'))
            ->orderBy('a.series_part')
            ->orderBy('a.published_at')
            ->get(ArticleCardHydrator::CARD_COLUMNS);

        return new SeriesPage(
            ArticleCardHydrator::string($row, 'title'),
            ArticleCardHydrator::string($row, 'slug'),
            ArticleCardHydrator::nullable($row, 'description'),
            $this->cards->cards($parts),
        );
    }

    private static function categoryRef(stdClass $row): CategoryRef
    {
        return new CategoryRef(
            ArticleCardHydrator::int($row, 'id'),
            ArticleCardHydrator::string($row, 'name'),
            ArticleCardHydrator::string($row, 'slug'),
            ArticleCardHydrator::nullable($row, 'icon'),
        );
    }

    private static function row(mixed $row): stdClass
    {
        return $row instanceof stdClass ? $row : new stdClass;
    }
}
