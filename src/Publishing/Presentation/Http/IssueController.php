<?php

declare(strict_types=1);

namespace Elaf\Publishing\Presentation\Http;

use Elaf\Content\Application\Query\ArticleQueries;
use Elaf\Content\Presentation\Http\Controllers\PublicPage;
use Elaf\Publishing\Application\Download\RecordIssueDownloadHandler;
use Elaf\Publishing\Application\Query\IssueQueries;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * FR-ISS-03, 04 and the PDF download (PRD §9).
 */
final readonly class IssueController
{
    public const PER_PAGE = 24;

    public function __construct(
        private IssueQueries $issues,
        private ArticleQueries $articles,
    ) {}

    public function index(Request $request): View
    {
        $years = $this->issues->years();
        $year = $request->query('year');
        $year = is_numeric($year) ? (int) $year : null;
        abort_if($year !== null && ! in_array($year, $years, true), 404);

        $issues = $this->issues->paginate($year, PublicPage::page($request), self::PER_PAGE);
        PublicPage::abortPastEnd($issues);

        return view('publishing.index', ['issues' => $issues, 'years' => $years, 'year' => $year]);
    }

    public function show(int $number): View
    {
        $issue = $this->issues->detail($number) ?? abort(404);

        return view('publishing.show', ['issue' => $issue, 'mostRead' => $this->articles->mostRead(PublicPage::MOST_READ)]);
    }

    /**
     * Counts, then hands the file to the web server (no PHP worker held for
     * a large PDF; the URL is immutable, see IssueStorageLayout).
     */
    public function pdf(int $number, RecordIssueDownloadHandler $downloads): RedirectResponse
    {
        $download = $this->issues->download($number) ?? abort(404);
        $downloads->handle($download->id);

        return redirect()->to(IssueMedia::url($download->pdfPath));
    }
}
