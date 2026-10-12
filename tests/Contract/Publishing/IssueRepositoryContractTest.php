<?php

declare(strict_types=1);

use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Domain\Issue\Exception\IssueNotFound;
use Elaf\Publishing\Domain\Issue\Issue;
use Elaf\Publishing\Domain\Issue\IssueFile;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Domain\Issue\IssueNumber;
use Elaf\Publishing\Domain\Issue\IssuePage;
use Elaf\Publishing\Domain\Issue\IssueRendering;
use Elaf\Publishing\Domain\Issue\IssueRepository;
use Elaf\Publishing\Domain\Issue\ProcessingStatus;
use Elaf\Publishing\Domain\Issue\PublicationDate;
use Elaf\Publishing\Infrastructure\Persistence\EloquentIssueRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\InMemoryIssueRepository;

uses(RefreshDatabase::class);

dataset('issue repositories', [
    'in-memory' => fn (): IssueRepository => new InMemoryIssueRepository,
    'eloquent' => fn (): IssueRepository => new EloquentIssueRepository(app(ConnectionInterface::class)),
]);

function contract_issue(IssueRepository $repository, int $number, string $content = 'x'): Issue
{
    return Issue::upload(
        $repository->nextId(),
        new IssueNumber($number),
        PublicationDate::fromString('2026-10-08'),
        'عدد تجريبي',
        new IssueFile("issues/{$number}/abc/elaf-{$number}.pdf", 4096, hash('sha256', $content)),
        null,
        new FrozenClock,
    );
}

it('hands out distinct ids', function (IssueRepository $repository): void {
    expect($repository->nextId()->equals($repository->nextId()))->toBeFalse();
})->with('issue repositories');

it('round-trips a pending issue and finds it by number', function (IssueRepository $repository): void {
    $issue = contract_issue($repository, 120);
    $repository->save($issue);

    $loaded = $repository->get($issue->id());
    expect($loaded->number()->value)->toBe(120)
        ->and($loaded->publishedOn()->value)->toBe('2026-10-08')
        ->and($loaded->title())->toBe('عدد تجريبي')
        ->and($loaded->file()->equals($issue->file()))->toBeTrue()
        ->and($loaded->status())->toBe(ProcessingStatus::Pending)
        ->and($loaded->rendering())->toBeNull()
        ->and($loaded->releaseEvents())->toBe([])
        ->and($repository->findByNumber(new IssueNumber(120))?->id()->value)->toBe($issue->id()->value)
        ->and($repository->findByNumber(new IssueNumber(121)))->toBeNull();
})->with('issue repositories');

it('round-trips the rendered pages, then a failure, then deletes', function (IssueRepository $repository): void {
    $clock = new FrozenClock('2026-10-08 11:30:00');
    $issue = contract_issue($repository, 120);
    $issue->startProcessing();
    $issue->markReady(new IssueRendering([
        new IssuePage(1, 'p/001.webp', 'p/001-thumb.webp', 1100, 1556),
        new IssuePage(2, 'p/002.webp', 'p/002-thumb.webp', 1100, 1560),
    ], 'p/social.jpg'), $clock);
    $repository->save($issue);

    $ready = $repository->get($issue->id());
    expect($ready->status())->toBe(ProcessingStatus::Ready)
        ->and($ready->pageCount())->toBe(2)
        ->and($ready->rendering()?->pages[1]->height)->toBe(1560)
        ->and($ready->rendering()?->socialImagePath)->toBe('p/social.jpg')
        ->and($ready->processedAt()?->getTimestamp())->toBe($clock->now()->getTimestamp());

    $ready->replaceFile(new IssueFile('issues/120/def/elaf-120.pdf', 10, hash('sha256', 'y')), UserId::fromInt(1), $clock);
    $ready->startProcessing();
    $ready->markFailed('pdf_unreadable', $clock);
    $repository->save($ready);

    $failed = $repository->get($issue->id());
    expect($failed->status())->toBe(ProcessingStatus::Failed)
        ->and($failed->processingError())->toBe('pdf_unreadable')
        ->and($failed->rendering())->toBeNull()
        ->and($failed->file()->path)->toBe('issues/120/def/elaf-120.pdf');

    $repository->delete($issue->id());
    expect(fn () => $repository->get($issue->id()))->toThrow(IssueNotFound::class);
})->with('issue repositories');

it('throws for an unknown id', function (IssueRepository $repository): void {
    expect(fn () => $repository->get(IssueId::fromInt(999)))->toThrow(IssueNotFound::class);
})->with('issue repositories');
