<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Query;

use DateTimeImmutable;
use DateTimeZone;
use Elaf\Content\Application\Query\ArticleCard;
use Elaf\Content\Application\Query\AuthorRef;
use Elaf\Content\Application\Query\CategoryRef;
use Elaf\Content\Application\Query\ImageRef;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleStatus;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use stdClass;

/**
 * Builds ArticleCard read models: one query for the articles (joined with their
 * category) and one for all their bylines, so lists never load per row.
 */
final readonly class ArticleCardHydrator
{
    public const CARD_COLUMNS = [
        'a.id', 'a.title', 'a.slug', 'a.kicker', 'a.excerpt', 'a.published_at', 'a.featured_image_path',
        'a.featured_image_caption', 'a.featured_image_credit', 'a.reading_minutes', 'a.origin',
        'c.id as category_id', 'c.name as category_name', 'c.slug as category_slug', 'c.icon as category_icon',
    ];

    public function __construct(private ConnectionInterface $db) {}

    /**
     * Published, not deleted articles joined with their category, as "a" and "c".
     */
    public function published(): Builder
    {
        return $this->db->table('articles as a')
            ->join('categories as c', 'c.id', '=', 'a.category_id')
            ->where('a.status', ArticleStatus::Published->value)
            ->whereNull('a.deleted_at');
    }

    /**
     * @param  iterable<mixed>  $rows  rows selected with CARD_COLUMNS
     * @return list<ArticleCard>
     */
    public function cards(iterable $rows): array
    {
        $rows = array_values(array_filter(is_array($rows) ? $rows : iterator_to_array($rows), static fn (mixed $r): bool => $r instanceof stdClass));
        $authors = $this->authorsOf(array_map(static fn (stdClass $r): int => self::int($r, 'id'), $rows));

        return array_map(fn (stdClass $r): ArticleCard => new ArticleCard(
            id: self::int($r, 'id'),
            title: self::string($r, 'title'),
            slug: self::string($r, 'slug'),
            kicker: self::nullable($r, 'kicker'),
            excerpt: self::nullable($r, 'excerpt'),
            publishedAt: self::date($r, 'published_at'),
            category: new CategoryRef(self::int($r, 'category_id'), self::string($r, 'category_name'), self::string($r, 'category_slug'), self::nullable($r, 'category_icon')),
            authors: $authors[self::int($r, 'id')] ?? [],
            image: self::nullable($r, 'featured_image_path') === null ? null : new ImageRef(
                self::string($r, 'featured_image_path'),
                self::nullable($r, 'featured_image_caption'),
                self::nullable($r, 'featured_image_credit'),
            ),
            readingMinutes: self::int($r, 'reading_minutes'),
            byContributor: self::string($r, 'origin') === ArticleOrigin::Contributor->value,
        ), $rows);
    }

    /**
     * @param  list<int>  $articleIds
     * @return array<int, list<AuthorRef>>
     */
    private function authorsOf(array $articleIds): array
    {
        if ($articleIds === []) {
            return [];
        }

        $byArticle = [];
        $rows = $this->db->table('article_author as aa')
            ->join('authors as au', 'au.id', '=', 'aa.author_id')
            ->whereIn('aa.article_id', $articleIds)
            ->whereNull('au.deleted_at')
            ->orderBy('aa.article_id')
            ->orderBy('aa.position')
            ->get(['aa.article_id', 'au.name', 'au.slug', 'au.title', 'au.photo_path']);

        foreach ($rows as $row) {
            $byArticle[self::int($row, 'article_id')][] = new AuthorRef(
                self::string($row, 'name'),
                self::string($row, 'slug'),
                self::nullable($row, 'title'),
                self::nullable($row, 'photo_path'),
            );
        }

        return $byArticle;
    }

    public static function int(stdClass $row, string $key): int
    {
        $value = $row->{$key} ?? null;

        return is_numeric($value) ? (int) $value : 0;
    }

    public static function string(stdClass $row, string $key): string
    {
        $value = $row->{$key} ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    public static function nullable(stdClass $row, string $key): ?string
    {
        $value = $row->{$key} ?? null;

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    /**
     * DATETIME columns hold app-timezone wall time (see LocalTime).
     */
    public static function date(stdClass $row, string $key): DateTimeImmutable
    {
        return new DateTimeImmutable(self::string($row, $key) ?: 'now', new DateTimeZone(date_default_timezone_get()));
    }
}
