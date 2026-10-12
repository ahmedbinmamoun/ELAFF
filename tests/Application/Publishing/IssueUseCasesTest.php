<?php

declare(strict_types=1);

use Elaf\Publishing\Application\Delete\DeleteIssueCommand;
use Elaf\Publishing\Application\Delete\DeleteIssueHandler;
use Elaf\Publishing\Application\Download\RecordIssueDownloadHandler;
use Elaf\Publishing\Application\Fail\FailIssueProcessingCommand;
use Elaf\Publishing\Application\Fail\FailIssueProcessingHandler;
use Elaf\Publishing\Application\Import\ImportIssuesFromDirectoryHandler;
use Elaf\Publishing\Application\Import\IssueImportItem;
use Elaf\Publishing\Application\Import\IssueImportPlan;
use Elaf\Publishing\Application\Issue\IssueInput;
use Elaf\Publishing\Application\IssuePermission;
use Elaf\Publishing\Application\Process\ProcessIssuePdfCommand;
use Elaf\Publishing\Application\Process\ProcessIssuePdfHandler;
use Elaf\Publishing\Application\Retry\RetryIssueProcessingCommand;
use Elaf\Publishing\Application\Retry\RetryIssueProcessingHandler;
use Elaf\Publishing\Application\Update\UpdateIssueCommand;
use Elaf\Publishing\Application\Update\UpdateIssueHandler;
use Elaf\Publishing\Application\Upload\UploadIssueCommand;
use Elaf\Publishing\Application\Upload\UploadIssueHandler;
use Elaf\Publishing\Domain\Issue\Events\IssueDeleted;
use Elaf\Publishing\Domain\Issue\Events\IssueProcessed;
use Elaf\Publishing\Domain\Issue\Events\IssueProcessingFailed;
use Elaf\Publishing\Domain\Issue\Events\IssueProcessingRetried;
use Elaf\Publishing\Domain\Issue\Events\IssueUploaded;
use Elaf\Publishing\Domain\Issue\Exception\IllegalIssueTransition;
use Elaf\Publishing\Domain\Issue\ProcessingStatus;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Tests\Support\PublishingKit;

beforeEach(function (): void {
    $this->kit = new PublishingKit;
    $this->upload = fn (IssueInput $input, string $pdf) => $this->kit->make(UploadIssueHandler::class)
        ->handle(new UploadIssueCommand(PublishingKit::EDITOR, $input, $pdf));
    $this->reason = static function (callable $work): string {
        try {
            $work();
        } catch (InvalidInput $e) {
            return $e->field.':'.$e->reason;
        }

        return 'none';
    };
});

it('uploads a PDF as a pending issue and announces it (FR-ISS-01, 02)', function (): void {
    $id = ($this->upload)(new IssueInput(120, '2026-10-08', ' عدد خاص '), $this->kit->files->pdf('/tmp/a.pdf', 'aaa'));
    $issue = $this->kit->get($id);
    $checksum = hash('sha256', 'aaa');

    expect($issue->status())->toBe(ProcessingStatus::Pending)
        ->and($issue->title())->toBe('عدد خاص')
        ->and($issue->file()->path)->toBe('issues/120/'.substr($checksum, 0, 12).'/elaf-120.pdf')
        ->and($issue->file()->checksum)->toBe($checksum)
        ->and($this->kit->files->stored)->toBe([$issue->file()->path => '/tmp/a.pdf'])
        ->and($this->kit->events->ofType(IssueUploaded::class))->toHaveCount(1);
});

it('validates the upload field by field', function (Closure $input, string $expected): void {
    $this->kit->pending(120);
    [$fields, $pdf] = $input($this->kit->files);

    expect(($this->reason)(fn () => ($this->upload)($fields, $pdf)))->toBe($expected)
        ->and($this->kit->issues->count())->toBe(1);
})->with([
    'number taken' => [fn ($f) => [new IssueInput(120, '2026-10-08'), $f->pdf('/tmp/x.pdf')], 'number:issue_number_taken'],
    'number 0' => [fn ($f) => [new IssueInput(0, '2026-10-08'), $f->pdf('/tmp/x.pdf')], 'number:number_not_positive'],
    'bad date' => [fn ($f) => [new IssueInput(121, '2026-13-01'), $f->pdf('/tmp/x.pdf')], 'published_on:invalid_date'],
    'long title' => [fn ($f) => [new IssueInput(121, '2026-10-15', str_repeat('ع', 201)), $f->pdf('/tmp/x.pdf')], 'title:too_long'],
    'not a PDF' => [fn ($f) => [new IssueInput(121, '2026-10-15'), $f->notPdf('/tmp/x.pdf.exe')], 'pdf:not_pdf'],
    'too large' => [fn ($f) => [new IssueInput(121, '2026-10-15'), $f->pdf('/tmp/big.pdf', 'big', 151 * 1024 * 1024)], 'pdf:file_too_large'],
    'missing file' => [fn ($f) => [new IssueInput(121, '2026-10-15'), '/tmp/nothing.pdf'], 'pdf:not_pdf'],
]);

it('needs the issues permission for every editor action (PRD §2)', function (): void {
    $issue = $this->kit->pending(120);
    $this->kit->authorizer->revoke(IssuePermission::ManageIssues->value);
    $id = $issue->id()->value;

    expect(fn () => ($this->upload)(new IssueInput(121, '2026-10-15'), $this->kit->files->pdf('/tmp/b.pdf')))->toThrow(NotAuthorized::class)
        ->and(fn () => $this->kit->make(UpdateIssueHandler::class)->handle(new UpdateIssueCommand(1, $id, new IssueInput(120, '2026-10-08'))))->toThrow(NotAuthorized::class)
        ->and(fn () => $this->kit->make(RetryIssueProcessingHandler::class)->handle(new RetryIssueProcessingCommand(1, $id)))->toThrow(NotAuthorized::class)
        ->and(fn () => $this->kit->make(DeleteIssueHandler::class)->handle(new DeleteIssueCommand(1, $id)))->toThrow(NotAuthorized::class);
});

it('renders every page to full and thumbnail WebP plus a social image', function (): void {
    $issue = $this->kit->ready(120, pages: 3);
    $dir = dirname($issue->file()->path);

    expect($issue->status())->toBe(ProcessingStatus::Ready)
        ->and($issue->pageCount())->toBe(3)
        ->and($issue->rendering()?->pages[2]->imagePath)->toBe("{$dir}/pages/003.webp")
        ->and($issue->rendering()?->pages[2]->thumbPath)->toBe("{$dir}/pages/003-thumb.webp")
        ->and($issue->rendering()?->socialImagePath)->toBe("{$dir}/social.jpg")
        ->and($this->kit->images->written)->toHaveCount(6)
        ->and($this->kit->images->written["{$dir}/pages/001.webp"])->toBe(1100)
        ->and($this->kit->images->written["{$dir}/pages/001-thumb.webp"])->toBe(320)
        ->and($this->kit->images->socialCard)->toBe("{$dir}/social.jpg")
        ->and($this->kit->rasterizer->discarded)->toBe(1)
        ->and($this->kit->events->ofType(IssueProcessed::class))->toHaveCount(1);
});

it('processes only pending issues, so a repeated job does nothing', function (): void {
    $issue = $this->kit->ready(120);
    $this->kit->make(ProcessIssuePdfHandler::class)->handle(new ProcessIssuePdfCommand($issue->id()->value));

    expect($this->kit->rasterizer->rasterized)->toHaveCount(1);
});

it('marks the issue failed with a reason instead of throwing (FR-ISS-06)', function (Closure $breakIt, string $reason): void {
    $issue = $this->kit->pending(120);
    $breakIt($this->kit);

    $this->kit->make(ProcessIssuePdfHandler::class)->handle(new ProcessIssuePdfCommand($issue->id()->value));
    $failed = $this->kit->get($issue->id());

    expect($failed->status())->toBe(ProcessingStatus::Failed)
        ->and($failed->processingError())->toBe($reason)
        ->and($this->kit->events->ofType(IssueProcessingFailed::class))->toHaveCount(1);
})->with([
    'unreadable PDF' => [fn ($kit) => $kit->rasterizer->failWith = 'pdf_unreadable', 'pdf_unreadable'],
    'no pages' => [fn ($kit) => $kit->rasterizer->pages = 0, 'no_pages'],
    'image conversion' => [fn ($kit) => $kit->images->fail = true, 'image_failed'],
]);

it('retries a failed issue: back to pending and queued again', function (): void {
    $issue = $this->kit->pending(120);
    $this->kit->rasterizer->failWith = 'timeout';
    $this->kit->make(ProcessIssuePdfHandler::class)->handle(new ProcessIssuePdfCommand($issue->id()->value));
    $retry = fn () => $this->kit->make(RetryIssueProcessingHandler::class)->handle(new RetryIssueProcessingCommand(PublishingKit::EDITOR, $issue->id()->value));

    $retry();
    expect($this->kit->get($issue->id())->status())->toBe(ProcessingStatus::Pending)
        ->and($this->kit->events->ofType(IssueProcessingRetried::class))->toHaveCount(1)
        ->and($retry)->toThrow(IllegalIssueTransition::class);

    $this->kit->rasterizer->failWith = null;
    $this->kit->make(ProcessIssuePdfHandler::class)->handle(new ProcessIssuePdfCommand($issue->id()->value));
    expect($this->kit->get($issue->id())->status())->toBe(ProcessingStatus::Ready);
});

it('records a crashed job as a failure, but never undoes a finished one', function (): void {
    $pending = $this->kit->pending(120);
    $ready = $this->kit->ready(121);
    $fail = fn (int $id) => $this->kit->make(FailIssueProcessingHandler::class)->handle(new FailIssueProcessingCommand($id, 'job_crashed'));

    $fail($pending->id()->value);
    $fail($ready->id()->value);

    expect($this->kit->get($pending->id())->processingError())->toBe('job_crashed')
        ->and($this->kit->get($ready->id())->status())->toBe(ProcessingStatus::Ready);
});

it('corrects details, and a new PDF replaces the old files', function (): void {
    $issue = $this->kit->ready(120);
    $oldDir = dirname($issue->file()->path);
    $update = fn (IssueInput $input, ?string $pdf = null) => $this->kit->make(UpdateIssueHandler::class)
        ->handle(new UpdateIssueCommand(PublishingKit::EDITOR, $issue->id()->value, $input, $pdf));

    $update(new IssueInput(120, '2026-10-09', 'تصحيح'));
    expect($this->kit->get($issue->id())->publishedOn()->value)->toBe('2026-10-09')
        ->and($this->kit->get($issue->id())->status())->toBe(ProcessingStatus::Ready)
        ->and($this->kit->files->deletedDirectories)->toBe([]);

    $update(new IssueInput(120, '2026-10-09', 'تصحيح'), $this->kit->files->pdf('/tmp/new.pdf', 'new content'));
    $replaced = $this->kit->get($issue->id());

    expect($replaced->status())->toBe(ProcessingStatus::Pending)
        ->and(dirname($replaced->file()->path))->not->toBe($oldDir)
        ->and($this->kit->files->deletedDirectories)->toBe([$oldDir])
        ->and($this->kit->events->ofType(IssueUploaded::class))->toHaveCount(2);
});

it('keeps numbers unique when correcting', function (): void {
    $this->kit->pending(119, '2026-10-01', 'one');
    $issue = $this->kit->pending(120, '2026-10-08', 'two');

    expect(($this->reason)(fn () => $this->kit->make(UpdateIssueHandler::class)->handle(
        new UpdateIssueCommand(PublishingKit::EDITOR, $issue->id()->value, new IssueInput(119, '2026-10-08')),
    )))->toBe('number:issue_number_taken');
});

it('deletes an issue and its files, unless articles are linked', function (): void {
    $linked = $this->kit->ready(119);
    $free = $this->kit->ready(120);
    $this->kit->counters->links[$linked->id()->value] = 2;
    $delete = fn ($issue) => $this->kit->make(DeleteIssueHandler::class)->handle(new DeleteIssueCommand(PublishingKit::EDITOR, $issue->id()->value));

    expect(($this->reason)(fn () => $delete($linked)))->toBe('issue:issue_has_articles');

    $delete($free);
    expect($this->kit->issues->count())->toBe(1)
        ->and($this->kit->files->deletedDirectories)->toBe([dirname($free->file()->path)])
        ->and($this->kit->events->ofType(IssueDeleted::class))->toHaveCount(1);
});

it('counts PDF downloads', function (): void {
    $issue = $this->kit->ready(120);
    $handler = $this->kit->make(RecordIssueDownloadHandler::class);

    $handler->handle($issue->id()->value);
    $handler->handle($issue->id()->value);

    expect($this->kit->counters->downloads[$issue->id()->value])->toBe(2);
});

it('imports a plan without a user, skipping numbers that exist (FR-ISS-05)', function (): void {
    $this->kit->pending(119);
    $this->kit->authorizer->revoke(IssuePermission::ManageIssues->value);
    $files = $this->kit->files;

    $report = $this->kit->make(ImportIssuesFromDirectoryHandler::class)->handle(new IssueImportPlan([
        new IssueImportItem($files->pdf('/in/a.pdf', 'a'), new IssueInput(119, '2026-10-01')),
        new IssueImportItem($files->pdf('/in/b.pdf', 'b'), new IssueInput(120, '2026-10-08')),
        new IssueImportItem($files->notPdf('/in/c.pdf'), new IssueInput(121, '2026-10-15')),
    ], []));

    expect($report->imported)->toBe(['b.pdf' => 120])
        ->and($report->skipped)->toBe(['a.pdf' => 119])
        ->and($report->failed)->toBe(['c.pdf' => 'not_pdf'])
        ->and($this->kit->events->ofType(IssueUploaded::class))->toHaveCount(2)
        ->and($this->kit->events->ofType(IssueUploaded::class)[1]->actorId)->toBeNull();
});
