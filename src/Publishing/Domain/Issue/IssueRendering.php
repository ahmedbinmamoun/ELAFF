<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue;

use Elaf\Publishing\Domain\Issue\Exception\InvalidIssue;

/**
 * The result of rasterizing an issue PDF: every page in order, plus the
 * social sharing image made from the cover (PRD §11 step 4). The cover is
 * page 1.
 */
final readonly class IssueRendering
{
    /** @var non-empty-list<IssuePage> */
    public array $pages;

    /**
     * @param  list<IssuePage>  $pages
     */
    public function __construct(array $pages, public string $socialImagePath)
    {
        if ($pages === []) {
            throw InvalidIssue::because('no_pages', 'A rendered issue has at least one page.');
        }

        foreach ($pages as $index => $page) {
            if ($page->number !== $index + 1) {
                throw InvalidIssue::because('pages_out_of_order', sprintf('Expected page %d, got %d.', $index + 1, $page->number));
            }
        }

        $this->pages = $pages;
    }

    public function pageCount(): int
    {
        return count($this->pages);
    }

    public function cover(): IssuePage
    {
        return $this->pages[0];
    }
}
