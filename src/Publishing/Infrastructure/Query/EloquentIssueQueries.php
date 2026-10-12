<?php

declare(strict_types=1);

namespace Elaf\Publishing\Infrastructure\Query;

use DateTimeImmutable;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Publishing\Application\Query\IssueDetail;
use Elaf\Publishing\Application\Query\IssueDownload;
use Elaf\Publishing\Application\Query\IssuePageView;
use Elaf\Publishing\Application\Query\IssueQueries;
use Elaf\Publishing\Application\Query\IssueSummary;
use Elaf\Publishing\Application\Query\IssueTocEntry;
use Elaf\Publishing\Domain\Issue\ProcessingStatus;
use Elaf\Shared\Application\Query\Paginated;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use stdClass;

/**
 * Ready issues only. Joins issues.cover_* (page 1) so lists need one query.
 */
final readonly class EloquentIssueQueries implements IssueQueries
{
    private const SUMMARY_COLUMNS = [
        'i.id', 'i.number', 'i.published_on', 'i.title', 'i.page_count', 'i.cover_path', 'i.cover_thumb_path',
        'i.pdf_size', 'p.width as cover_width', 'p.height as cover_height',
    ];

    public function __construct(private ConnectionInterface $db) {}

    public function latest(): ?IssueSummary
    {
        $row = $this->ready()->orderByDesc('i.number')->first(self::SUMMARY_COLUMNS);

        return $row instanceof stdClass ? $this->summary($row) : null;
    }

    public function paginate(?int $year, int $page, int $perPage): Paginated
    {
        $query = $this->ready()->when($year !== null, static fn (Builder $q) => $q->whereBetween('i.published_on', [
            sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year),
        ]));
        $total = (clone $query)->count();
        $rows = $query->orderByDesc('i.number')->forPage($page, $perPage)->get(self::SUMMARY_COLUMNS);

        return new Paginated($this->summaries($rows), $total, $page, $perPage);
    }

    public function years(): array
    {
        return array_values($this->db->table('issues')
            ->where('processing_status', ProcessingStatus::Ready->value)
            ->orderByDesc('published_on')
            ->pluck('published_on')
            ->map(static fn (mixed $date): int => (int) substr(is_scalar($date) ? (string) $date : '', 0, 4))
            ->unique()
            ->values()
            ->all());
    }

    public function detail(int $number): ?IssueDetail
    {
        $row = $this->ready()->where('i.number', $number)
            ->first([...self::SUMMARY_COLUMNS, 'i.pdf_path', 'i.social_image_path']);

        if (! $row instanceof stdClass) {
            return null;
        }

        $summary = $this->summary($row);
        $pages = $this->db->table('issue_pages')
            ->where('issue_id', $summary->id)
            ->orderBy('page_number')
            ->get(['page_number', 'image_path', 'thumb_path', 'width', 'height'])
            ->map(static fn (mixed $p): IssuePageView => new IssuePageView(
                self::int($p, 'page_number'), self::string($p, 'image_path'), self::string($p, 'thumb_path'),
                self::int($p, 'width'), self::int($p, 'height'),
            ));

        $contents = $this->db->table('articles as a')
            ->join('categories as c', 'c.id', '=', 'a.category_id')
            ->where('a.issue_id', $summary->id)
            ->where('a.status', ArticleStatus::Published->value)
            ->whereNull('a.deleted_at')
            ->orderBy('a.issue_page')
            ->orderBy('a.title')
            ->get(['a.issue_page', 'a.id', 'a.slug', 'a.title', 'a.kicker', 'a.print_section', 'c.name as category'])
            ->map(static fn (mixed $a): IssueTocEntry => new IssueTocEntry(
                self::int($a, 'issue_page'), self::int($a, 'id'), self::string($a, 'slug'), self::string($a, 'title'),
                self::nullable($a, 'kicker'), self::nullable($a, 'print_section') ?? self::nullable($a, 'category'),
            ));

        $previous = $this->db->table('issues')->where('processing_status', ProcessingStatus::Ready->value)->where('number', '<', $number)->max('number');
        $next = $this->db->table('issues')->where('processing_status', ProcessingStatus::Ready->value)->where('number', '>', $number)->min('number');

        return new IssueDetail(
            summary: $summary,
            pdfPath: self::string($row, 'pdf_path'),
            socialImagePath: self::nullable($row, 'social_image_path'),
            pages: array_values($pages->all()),
            contents: array_values($contents->all()),
            previousNumber: is_numeric($previous) ? (int) $previous : null,
            nextNumber: is_numeric($next) ? (int) $next : null,
        );
    }

    public function download(int $number): ?IssueDownload
    {
        $row = $this->db->table('issues')
            ->where('processing_status', ProcessingStatus::Ready->value)
            ->where('number', $number)
            ->first(['id', 'number', 'pdf_path']);

        return $row instanceof stdClass
            ? new IssueDownload(self::int($row, 'id'), self::int($row, 'number'), self::string($row, 'pdf_path'))
            : null;
    }

    private function ready(): Builder
    {
        return $this->db->table('issues as i')
            ->join('issue_pages as p', static fn (JoinClause $join) => $join->on('p.issue_id', '=', 'i.id')->where('p.page_number', 1))
            ->where('i.processing_status', ProcessingStatus::Ready->value);
    }

    /**
     * @param  iterable<mixed>  $rows
     * @return list<IssueSummary>
     */
    private function summaries(iterable $rows): array
    {
        $summaries = [];

        foreach ($rows as $row) {
            if ($row instanceof stdClass) {
                $summaries[] = $this->summary($row);
            }
        }

        return $summaries;
    }

    private function summary(stdClass $row): IssueSummary
    {
        return new IssueSummary(
            id: self::int($row, 'id'),
            number: self::int($row, 'number'),
            publishedOn: new DateTimeImmutable(substr(self::string($row, 'published_on'), 0, 10)),
            title: self::nullable($row, 'title'),
            pageCount: self::int($row, 'page_count'),
            coverPath: self::string($row, 'cover_path'),
            coverThumbPath: self::string($row, 'cover_thumb_path'),
            coverWidth: self::int($row, 'cover_width'),
            coverHeight: self::int($row, 'cover_height'),
            pdfSize: self::int($row, 'pdf_size'),
        );
    }

    private static function int(mixed $row, string $key): int
    {
        $value = $row instanceof stdClass ? ($row->{$key} ?? null) : null;

        return is_numeric($value) ? (int) $value : 0;
    }

    private static function string(mixed $row, string $key): string
    {
        $value = $row instanceof stdClass ? ($row->{$key} ?? null) : null;

        return is_scalar($value) ? (string) $value : '';
    }

    private static function nullable(mixed $row, string $key): ?string
    {
        $value = self::string($row, $key);

        return $value === '' ? null : $value;
    }
}
