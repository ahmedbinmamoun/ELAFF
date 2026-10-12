<?php

declare(strict_types=1);

use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Domain\Issue\Events\IssueProcessed;
use Elaf\Publishing\Domain\Issue\Events\IssueProcessingFailed;
use Elaf\Publishing\Domain\Issue\Events\IssueProcessingRetried;
use Elaf\Publishing\Domain\Issue\Events\IssueRevised;
use Elaf\Publishing\Domain\Issue\Events\IssueUploaded;
use Elaf\Publishing\Domain\Issue\Exception\IllegalIssueTransition;
use Elaf\Publishing\Domain\Issue\Exception\InvalidIssue;
use Elaf\Publishing\Domain\Issue\Issue;
use Elaf\Publishing\Domain\Issue\IssueFile;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Domain\Issue\IssueNumber;
use Elaf\Publishing\Domain\Issue\IssuePage;
use Elaf\Publishing\Domain\Issue\IssueRendering;
use Elaf\Publishing\Domain\Issue\ProcessingStatus;
use Elaf\Publishing\Domain\Issue\PublicationDate;
use Tests\Support\Fakes\FrozenClock;

function an_issue_file(string $content = 'a'): IssueFile
{
    return new IssueFile('issues/120/abc/elaf-120.pdf', 2048, hash('sha256', $content));
}

function a_rendering(int $pages = 2): IssueRendering
{
    return new IssueRendering(
        array_map(static fn (int $n): IssuePage => new IssuePage($n, "p/{$n}.webp", "p/{$n}-t.webp", 1100, 1556), range(1, $pages)),
        'social.jpg',
    );
}

beforeEach(function (): void {
    $this->clock = new FrozenClock('2026-10-08 10:00:00');
    $this->editor = UserId::fromInt(3);
    $this->issue = Issue::upload(IssueId::fromInt(1), new IssueNumber(120), PublicationDate::fromString('2026-10-08'), '  عدد خاص ', an_issue_file(), $this->editor, $this->clock);
});

it('starts pending, trims the title and records the upload', function (): void {
    expect($this->issue->status())->toBe(ProcessingStatus::Pending)
        ->and($this->issue->title())->toBe('عدد خاص')
        ->and($this->issue->pageCount())->toBeNull()
        ->and($this->issue->releaseEvents())->sequence(fn ($e) => $e->toBeInstanceOf(IssueUploaded::class));
});

it('goes pending → processing → ready with its pages (FR-ISS-02)', function (): void {
    $this->issue->startProcessing();
    $this->issue->markReady(a_rendering(3), $this->clock);

    expect($this->issue->status())->toBe(ProcessingStatus::Ready)
        ->and($this->issue->pageCount())->toBe(3)
        ->and($this->issue->rendering()?->cover()->number)->toBe(1)
        ->and($this->issue->processedAt())->toEqual($this->clock->now())
        ->and(array_map(get_class(...), $this->issue->releaseEvents()))->toBe([IssueUploaded::class, IssueProcessed::class]);
});

it('records a failure reason and can be retried (FR-ISS-06)', function (): void {
    $this->issue->startProcessing();
    $this->issue->markFailed('  pdf_unreadable ', $this->clock);

    expect($this->issue->status())->toBe(ProcessingStatus::Failed)
        ->and($this->issue->processingError())->toBe('pdf_unreadable');

    $this->issue->retry($this->editor, $this->clock);
    $this->issue->startProcessing();

    expect($this->issue->status())->toBe(ProcessingStatus::Processing)
        ->and($this->issue->processingError())->toBeNull()
        ->and(array_map(get_class(...), $this->issue->releaseEvents()))->toBe([IssueUploaded::class, IssueProcessingFailed::class, IssueProcessingRetried::class]);
});

it('refuses moves the status machine does not allow', function (string $move): void {
    $issue = $this->issue;
    $run = match ($move) {
        'ready without processing' => fn () => $issue->markReady(a_rendering(), $this->clock),
        'retry while pending' => fn () => $issue->retry($this->editor, $this->clock),
        'process twice' => function () use ($issue): void {
            $issue->startProcessing();
            $issue->startProcessing();
        },
        'fail a ready issue' => function () use ($issue): void {
            $issue->startProcessing();
            $issue->markReady(a_rendering(), $this->clock);
            $issue->markFailed('x', $this->clock);
        },
    };

    expect($run)->toThrow(IllegalIssueTransition::class);
})->with(['ready without processing', 'retry while pending', 'process twice', 'fail a ready issue']);

it('lets a pending issue fail (a job that never ran)', function (): void {
    $this->issue->markFailed('', $this->clock);

    expect($this->issue->status())->toBe(ProcessingStatus::Failed)
        ->and($this->issue->processingError())->toBe('unknown');
});

it('replacing the PDF throws the pages away and processes again', function (): void {
    $this->issue->startProcessing();
    $this->issue->markReady(a_rendering(), $this->clock);
    $this->issue->releaseEvents();

    $this->issue->replaceFile(an_issue_file('a'), $this->editor, $this->clock);
    expect($this->issue->releaseEvents())->toBe([]);

    $this->issue->replaceFile(an_issue_file('b'), $this->editor, $this->clock);
    expect($this->issue->status())->toBe(ProcessingStatus::Pending)
        ->and($this->issue->rendering())->toBeNull()
        ->and($this->issue->processedAt())->toBeNull()
        ->and($this->issue->releaseEvents())->sequence(fn ($e) => $e->toBeInstanceOf(IssueUploaded::class));
});

it('records a revision only when something changed', function (): void {
    $this->issue->releaseEvents();
    $this->issue->revise(new IssueNumber(120), PublicationDate::fromString('2026-10-08'), 'عدد خاص', $this->editor, $this->clock);
    expect($this->issue->releaseEvents())->toBe([]);

    $this->issue->revise(new IssueNumber(121), PublicationDate::fromString('2026-10-15'), null, $this->editor, $this->clock);
    expect($this->issue->number()->value)->toBe(121)
        ->and($this->issue->publishedOn()->value)->toBe('2026-10-15')
        ->and($this->issue->title())->toBeNull()
        ->and($this->issue->releaseEvents())->sequence(fn ($e) => $e->toBeInstanceOf(IssueRevised::class));
});

it('rejects invalid values', function (Closure $make, string $reason): void {
    try {
        $make();
        $this->fail('No exception.');
    } catch (InvalidIssue $e) {
        expect($e->reason)->toBe($reason);
    }
})->with([
    'number 0' => [fn () => new IssueNumber(0), 'number_not_positive'],
    'bad date' => [fn () => PublicationDate::fromString('2026-02-30'), 'invalid_date'],
    'not a date' => [fn () => PublicationDate::fromString('08/10/2026'), 'invalid_date'],
    'empty file' => [fn () => new IssueFile('x.pdf', 0, hash('sha256', 'x')), 'file_size'],
    'file over 150 MB' => [fn () => new IssueFile('x.pdf', IssueFile::MAX_BYTES + 1, hash('sha256', 'x')), 'file_size'],
    'bad checksum' => [fn () => new IssueFile('x.pdf', 10, 'abc'), 'file_checksum'],
    'no pages' => [fn () => new IssueRendering([], 's.jpg'), 'no_pages'],
    'pages out of order' => [fn () => new IssueRendering([new IssuePage(2, 'a', 'b', 1, 1)], 's.jpg'), 'pages_out_of_order'],
    'page without size' => [fn () => new IssuePage(1, 'a', 'b', 0, 10), 'page_size'],
    'title too long' => [fn () => Issue::upload(IssueId::fromInt(2), new IssueNumber(1), PublicationDate::fromString('2026-01-01'), str_repeat('ع', 201), an_issue_file(), null, new FrozenClock), 'title_too_long'],
]);

it('only reconstitutes a ready issue with pages', function (): void {
    expect(fn () => Issue::reconstitute(IssueId::fromInt(1), new IssueNumber(1), PublicationDate::fromString('2026-01-01'), null, an_issue_file(), ProcessingStatus::Ready, null, null, null))
        ->toThrow(InvalidIssue::class);
});
