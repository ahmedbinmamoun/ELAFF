<?php

declare(strict_types=1);

namespace Tests\Support;

use Elaf\Publishing\Domain\Issue\Issue;
use Elaf\Publishing\Domain\Issue\IssueFile;
use Elaf\Publishing\Domain\Issue\IssueNumber;
use Elaf\Publishing\Domain\Issue\IssuePage;
use Elaf\Publishing\Domain\Issue\IssueRendering;
use Elaf\Publishing\Domain\Issue\IssueRepository;
use Elaf\Publishing\Domain\Issue\PublicationDate;
use Elaf\Publishing\Infrastructure\Persistence\IssueModel;
use Tests\Support\Fakes\FrozenClock;

/**
 * Issues stored directly through the repository (no PDF rendering): for
 * pages and admin tests. File paths point nowhere.
 */
final class IssueFixtures
{
    public static function ready(int $number, string $date = '2026-10-08', int $pages = 4, ?string $title = null): IssueModel
    {
        $issue = self::issue($number, $date, $title);
        $dir = "issues/{$number}/abcdef123456";
        $issue->startProcessing();
        $issue->markReady(new IssueRendering(
            array_map(static fn (int $n): IssuePage => new IssuePage($n, sprintf('%s/pages/%03d.webp', $dir, $n), sprintf('%s/pages/%03d-thumb.webp', $dir, $n), 1100, 1556), range(1, $pages)),
            "{$dir}/social.jpg",
        ), new FrozenClock);

        return self::save($issue);
    }

    public static function failed(int $number, string $reason = 'pdf_unreadable'): IssueModel
    {
        $issue = self::issue($number);
        $issue->markFailed($reason, new FrozenClock);

        return self::save($issue);
    }

    public static function pending(int $number, string $date = '2026-10-08', ?string $title = null): IssueModel
    {
        return self::save(self::issue($number, $date, $title));
    }

    private static function issue(int $number, string $date = '2026-10-08', ?string $title = null): Issue
    {
        $repository = app(IssueRepository::class);

        return Issue::upload(
            $repository->nextId(),
            new IssueNumber($number),
            PublicationDate::fromString($date),
            $title,
            new IssueFile("issues/{$number}/abcdef123456/elaf-{$number}.pdf", 2 * 1024 * 1024, hash('sha256', (string) $number)),
            null,
            new FrozenClock,
        );
    }

    private static function save(Issue $issue): IssueModel
    {
        $issue->releaseEvents();
        app(IssueRepository::class)->save($issue);

        return IssueModel::query()->findOrFail($issue->id()->value);
    }
}
