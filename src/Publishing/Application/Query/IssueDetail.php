<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Query;

final readonly class IssueDetail
{
    /**
     * @param  list<IssuePageView>  $pages
     * @param  list<IssueTocEntry>  $contents  by page, then by title
     */
    public function __construct(
        public IssueSummary $summary,
        public string $pdfPath,
        public ?string $socialImagePath,
        public array $pages,
        public array $contents,
        public ?int $previousNumber,
        public ?int $nextNumber,
    ) {}

    /**
     * @return array<int, list<IssueTocEntry>> page number => entries
     */
    public function contentsByPage(): array
    {
        $byPage = [];

        foreach ($this->contents as $entry) {
            $byPage[$entry->page][] = $entry;
        }

        return $byPage;
    }
}
