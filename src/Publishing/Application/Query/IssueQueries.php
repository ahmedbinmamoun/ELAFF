<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Query;

use Elaf\Shared\Application\Query\Paginated;

/**
 * Public read side of the archive (FR-ISS-03, 04): ready issues only.
 * Each method runs a constant number of queries.
 */
interface IssueQueries
{
    /**
     * The newest issue by number, for the homepage block (FR-ISS-04).
     */
    public function latest(): ?IssueSummary;

    /**
     * Newest first.
     *
     * @return Paginated<IssueSummary>
     */
    public function paginate(?int $year, int $page, int $perPage): Paginated;

    /**
     * Years that have at least one issue, newest first.
     *
     * @return list<int>
     */
    public function years(): array;

    public function detail(int $number): ?IssueDetail;

    public function download(int $number): ?IssueDownload;
}
