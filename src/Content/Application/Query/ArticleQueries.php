<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Query;

use DateTimeImmutable;
use Elaf\Shared\Application\Query\Paginated;

/**
 * Public read side (PRD §5.6): published articles only, as DTOs. Each method
 * runs a constant number of queries, whatever the amount of content.
 */
interface ArticleQueries
{
    public function detail(int $articleId): ?ArticleDetail;

    /**
     * Featured articles by homepage priority, completed with the latest (FR-ART-07).
     *
     * @return list<ArticleCard>
     */
    public function hero(int $limit): array;

    /**
     * @param  list<int>  $exceptIds
     * @return list<ArticleCard>
     */
    public function latest(int $limit, array $exceptIds = []): array;

    /**
     * FR-ART-06: breaking articles whose expiry has not passed at $now.
     *
     * @return list<ArticleLink>
     */
    public function breaking(DateTimeImmutable $now, int $limit): array;

    /**
     * @param  list<int>  $categoryIds  in display order
     * @return list<CategorySection>
     */
    public function categorySections(array $categoryIds, int $perSection): array;

    /**
     * @return Paginated<ArticleCard>
     */
    public function byCategory(int $categoryId, int $page, int $perPage): Paginated;

    /**
     * @return Paginated<ArticleCard>
     */
    public function byAuthor(int $authorId, int $page, int $perPage): Paginated;

    /**
     * @return Paginated<ArticleCard>
     */
    public function byTag(string $tagSlug, int $page, int $perPage): Paginated;

    /**
     * Same category, newest first, without the article itself.
     *
     * @return list<ArticleCard>
     */
    public function related(int $articleId, int $categoryId, int $limit): array;

    /**
     * PRD §8 MostRead(period): by view count, then newest. Until view counting
     * (FR-ART-09) fills the counter this is effectively "latest".
     *
     * @return list<ArticleCard>
     */
    public function mostRead(int $limit, ?DateTimeImmutable $since = null): array;

    /**
     * Published articles among $ids, in the order given (reader favorites).
     *
     * @param  list<int>  $ids
     * @return list<ArticleCard>
     */
    public function cards(array $ids): array;

    /**
     * FR-AUT-02: columnists with their latest published column.
     *
     * @return list<ColumnistItem>
     */
    public function columnists(int $limit): array;
}
