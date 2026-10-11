<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Query;

use DateTimeImmutable;
use Elaf\Content\Application\Query\ArticleCard;
use Elaf\Content\Application\Query\ArticleDetail;
use Elaf\Content\Application\Query\ArticleLink;
use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Content\Application\Query\AuthorRef;
use Elaf\Content\Application\Query\CategorySection;
use Elaf\Content\Application\Query\ColumnistItem;
use Elaf\Content\Application\Query\Paginated;
use Elaf\Content\Application\Query\SeriesNavigation;
use Elaf\Content\Application\Query\TagRef;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Infrastructure\Persistence\Eloquent\LocalTime;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use stdClass;

final readonly class EloquentArticleQueries implements ArticleQueries
{
    public function __construct(
        private ConnectionInterface $db,
        private ArticleCardHydrator $cards,
    ) {}

    public function detail(int $articleId): ?ArticleDetail
    {
        $row = $this->cards->published()
            ->where('a.id', $articleId)
            ->first([...ArticleCardHydrator::CARD_COLUMNS, 'a.subtitle', 'a.dateline', 'a.body', 'a.source_name', 'a.source_url',
                'a.series_id', 'a.series_part', 'a.updated_at', 'a.seo_title', 'a.seo_description', 'a.canonical_url']);

        if (! $row instanceof stdClass) {
            return null;
        }

        $tags = $this->db->table('article_tag as at')
            ->join('tags as t', 't.id', '=', 'at.tag_id')
            ->where('at.article_id', $articleId)
            ->orderBy('t.name')
            ->get(['t.name', 't.slug'])
            ->map(static fn (mixed $t): TagRef => new TagRef(
                ArticleCardHydrator::string(self::row($t), 'name'),
                ArticleCardHydrator::string(self::row($t), 'slug'),
            ))
            ->values()
            ->all();

        return new ArticleDetail(
            card: $this->cards->cards([$row])[0],
            subtitle: ArticleCardHydrator::nullable($row, 'subtitle'),
            dateline: ArticleCardHydrator::nullable($row, 'dateline'),
            bodyHtml: ArticleCardHydrator::string($row, 'body'),
            sourceName: ArticleCardHydrator::string($row, 'source_name'),
            sourceUrl: ArticleCardHydrator::nullable($row, 'source_url'),
            tags: array_values($tags),
            series: $this->seriesNavigation($row),
            updatedAt: ArticleCardHydrator::date($row, 'updated_at'),
            seoTitle: ArticleCardHydrator::nullable($row, 'seo_title'),
            seoDescription: ArticleCardHydrator::nullable($row, 'seo_description'),
            canonicalUrl: ArticleCardHydrator::nullable($row, 'canonical_url'),
        );
    }

    public function hero(int $limit): array
    {
        return $this->cards->cards($this->cards->published()
            ->orderByDesc('a.is_featured')
            ->orderByDesc('a.homepage_priority')
            ->orderByDesc('a.published_at')
            ->orderByDesc('a.id')
            ->limit($limit)
            ->get(ArticleCardHydrator::CARD_COLUMNS));
    }

    public function latest(int $limit, array $exceptIds = []): array
    {
        return $this->cards->cards($this->newest($this->cards->published())
            ->when($exceptIds !== [], static fn (Builder $q) => $q->whereNotIn('a.id', $exceptIds))
            ->limit($limit)
            ->get(ArticleCardHydrator::CARD_COLUMNS));
    }

    public function breaking(DateTimeImmutable $now, int $limit): array
    {
        return array_values($this->newest($this->cards->published())
            ->where('a.is_breaking', true)
            ->where(static fn (Builder $q) => $q->whereNull('a.breaking_until')->orWhere('a.breaking_until', '>', LocalTime::format($now)))
            ->limit($limit)
            ->get(['a.id', 'a.title', 'a.slug'])
            ->map(static fn (mixed $r): ArticleLink => self::link(self::row($r)))
            ->all());
    }

    public function categorySections(array $categoryIds, int $perSection): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $ranked = $this->cards->published()
            ->whereIn('a.category_id', $categoryIds)
            ->select(ArticleCardHydrator::CARD_COLUMNS)
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY a.category_id ORDER BY a.published_at DESC, a.id DESC) as rank_in_category');

        $rows = $ranked->newQuery()->fromSub($ranked, 'ranked')
            ->where('rank_in_category', '<=', $perSection)
            ->orderBy('rank_in_category')
            ->get();

        $byCategory = [];
        foreach ($this->cards->cards($rows) as $card) {
            $byCategory[$card->category->id][] = $card;
        }

        $sections = [];
        foreach ($categoryIds as $id) {
            if (isset($byCategory[$id])) {
                $sections[] = new CategorySection($byCategory[$id][0]->category, $byCategory[$id]);
            }
        }

        return $sections;
    }

    public function byCategory(int $categoryId, int $page, int $perPage): Paginated
    {
        return $this->paginate($this->cards->published()->where('a.category_id', $categoryId), $page, $perPage);
    }

    public function byAuthor(int $authorId, int $page, int $perPage): Paginated
    {
        return $this->paginate(
            $this->cards->published()->whereExists(static fn (Builder $q) => $q
                ->from('article_author as aa')
                ->whereColumn('aa.article_id', 'a.id')
                ->where('aa.author_id', $authorId)),
            $page,
            $perPage,
        );
    }

    public function byTag(string $tagSlug, int $page, int $perPage): Paginated
    {
        return $this->paginate(
            $this->cards->published()->whereExists(static fn (Builder $q) => $q
                ->from('article_tag as at')
                ->join('tags as t', 't.id', '=', 'at.tag_id')
                ->whereColumn('at.article_id', 'a.id')
                ->where('t.slug', $tagSlug)),
            $page,
            $perPage,
        );
    }

    public function related(int $articleId, int $categoryId, int $limit): array
    {
        return $this->cards->cards($this->newest($this->cards->published())
            ->where('a.category_id', $categoryId)
            ->where('a.id', '!=', $articleId)
            ->limit($limit)
            ->get(ArticleCardHydrator::CARD_COLUMNS));
    }

    public function mostRead(int $limit, ?DateTimeImmutable $since = null): array
    {
        return $this->cards->cards($this->cards->published()
            ->when($since !== null, static fn (Builder $q) => $q->where('a.published_at', '>=', LocalTime::format($since ?? new DateTimeImmutable)))
            ->orderByDesc('a.views_count')
            ->orderByDesc('a.published_at')
            ->orderByDesc('a.id')
            ->limit($limit)
            ->get(ArticleCardHydrator::CARD_COLUMNS));
    }

    public function cards(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));

        if ($ids === []) {
            return [];
        }

        $byId = [];
        foreach ($this->cards->cards($this->cards->published()->whereIn('a.id', $ids)->get(ArticleCardHydrator::CARD_COLUMNS)) as $card) {
            $byId[$card->id] = $card;
        }

        return array_values(array_filter(array_map(static fn (int $id): ?ArticleCard => $byId[$id] ?? null, $ids)));
    }

    public function columnists(int $limit): array
    {
        $ranked = $this->db->table('authors as au')
            ->join('article_author as aa', 'aa.author_id', '=', 'au.id')
            ->join('articles as a', 'a.id', '=', 'aa.article_id')
            ->where('au.is_columnist', true)
            ->whereNull('au.deleted_at')
            ->where('a.status', ArticleStatus::Published->value)
            ->whereNull('a.deleted_at')
            ->select(['au.name', 'au.slug as author_slug', 'au.title as author_title', 'au.photo_path', 'a.id', 'a.title', 'a.slug', 'a.published_at'])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY au.id ORDER BY a.published_at DESC, a.id DESC) as rank_for_author');

        return array_values($ranked->newQuery()->fromSub($ranked, 'ranked')
            ->where('rank_for_author', 1)
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get()
            ->map(static fn (mixed $r): ColumnistItem => new ColumnistItem(
                new AuthorRef(
                    ArticleCardHydrator::string(self::row($r), 'name'),
                    ArticleCardHydrator::string(self::row($r), 'author_slug'),
                    ArticleCardHydrator::nullable(self::row($r), 'author_title'),
                    ArticleCardHydrator::nullable(self::row($r), 'photo_path'),
                ),
                self::link(self::row($r)),
                ArticleCardHydrator::date(self::row($r), 'published_at'),
            ))
            ->all());
    }

    /**
     * @return Paginated<ArticleCard>
     */
    private function paginate(Builder $query, int $page, int $perPage): Paginated
    {
        $page = max(1, $page);
        $total = (clone $query)->count();
        $rows = $this->newest($query)->offset(($page - 1) * $perPage)->limit($perPage)->get(ArticleCardHydrator::CARD_COLUMNS);

        return new Paginated($this->cards->cards($rows), $total, $page, $perPage);
    }

    private function newest(Builder $query): Builder
    {
        return $query->orderByDesc('a.published_at')->orderByDesc('a.id');
    }

    private function seriesNavigation(stdClass $article): ?SeriesNavigation
    {
        $seriesId = ArticleCardHydrator::int($article, 'series_id');
        $part = ArticleCardHydrator::int($article, 'series_part');

        if ($seriesId === 0) {
            return null;
        }

        $rows = $this->db->table('series as s')
            ->join('articles as a', 'a.series_id', '=', 's.id')
            ->where('s.id', $seriesId)
            ->where('a.status', ArticleStatus::Published->value)
            ->whereNull('a.deleted_at')
            ->orderBy('a.series_part')
            ->orderBy('a.published_at')
            ->get(['s.title as series_title', 's.slug as series_slug', 'a.id', 'a.title', 'a.slug', 'a.series_part'])
            ->map(static fn (mixed $r): stdClass => self::row($r))
            ->values()
            ->all();

        if ($rows === []) {
            return null;
        }

        $index = 0;
        foreach ($rows as $i => $r) {
            if (ArticleCardHydrator::int($r, 'id') === ArticleCardHydrator::int($article, 'id')) {
                $index = $i;
            }
        }

        return new SeriesNavigation(
            title: ArticleCardHydrator::string($rows[0], 'series_title'),
            slug: ArticleCardHydrator::string($rows[0], 'series_slug'),
            part: $part,
            parts: count($rows),
            previous: isset($rows[$index - 1]) ? self::link($rows[$index - 1]) : null,
            next: isset($rows[$index + 1]) ? self::link($rows[$index + 1]) : null,
        );
    }

    private static function link(stdClass $row): ArticleLink
    {
        return new ArticleLink(
            ArticleCardHydrator::int($row, 'id'),
            ArticleCardHydrator::string($row, 'title'),
            ArticleCardHydrator::string($row, 'slug'),
        );
    }

    private static function row(mixed $row): stdClass
    {
        return $row instanceof stdClass ? $row : new stdClass;
    }
}
