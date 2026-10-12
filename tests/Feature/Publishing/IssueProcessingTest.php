<?php

declare(strict_types=1);

use Database\Factories\Support\DemoPdf;
use Elaf\Publishing\Application\Issue\IssueInput;
use Elaf\Publishing\Application\Issue\IssueUploader;
use Elaf\Publishing\Infrastructure\Persistence\IssueModel;
use Elaf\Publishing\Infrastructure\Processing\ProcessIssuePdfJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\ExecutableFinder;

uses(RefreshDatabase::class);

/*
 * The real pipeline: upload → queued job (sync in tests) → pdftoppm →
 * Imagick WebP. Needs poppler and Imagick, present in the app image.
 */
beforeEach(function (): void {
    if ((new ExecutableFinder)->find('pdftoppm') === null || ! extension_loaded('imagick')) {
        $this->markTestSkipped('pdftoppm or Imagick is not installed.');
    }

    Storage::fake('public');
    $this->upload = function (string $content, int $number = 120): IssueModel {
        $file = (string) tempnam(sys_get_temp_dir(), 'elaf-test-');
        file_put_contents($file, $content);

        try {
            $id = app(IssueUploader::class)->upload(new IssueInput($number, '2026-10-08'), $file, null);
        } finally {
            @unlink($file);
        }

        return IssueModel::query()->with('pages')->findOrFail($id->value);
    };
});

it('renders a PDF to WebP pages, thumbnails and a social image (FR-ISS-02)', function (): void {
    $issue = ($this->upload)(DemoPdf::newspaper(120, 3));
    $disk = Storage::disk('public');

    expect($issue->processing_status)->toBe('ready')
        ->and($issue->page_count)->toBe(3)
        ->and($issue->pages)->toHaveCount(3)
        ->and($issue->cover_path)->toBe($issue->pages[0]->image_path)
        ->and($issue->pages[0]->width)->toBe(1100)
        ->and($issue->pages[0]->height)->toBeGreaterThan(1500);

    foreach ($issue->pages as $page) {
        $disk->assertExists([$page->image_path, $page->thumb_path]);
        expect(substr((string) $disk->get($page->image_path), 8, 4))->toBe('WEBP')
            ->and(getimagesizefromstring((string) $disk->get($page->thumb_path))[0] ?? 0)->toBe(320);
    }

    $social = getimagesizefromstring((string) $disk->get((string) $issue->social_image_path)) ?: [];
    expect([$social[0] ?? 0, $social[1] ?? 0, $social['mime'] ?? ''])->toBe([1200, 630, 'image/jpeg']);
    expect(glob(sys_get_temp_dir().'/elaf-issue-*'))->toBe([]);
});

it('marks a broken PDF as failed with a reason (FR-ISS-06)', function (): void {
    $issue = ($this->upload)("%PDF-1.4\n%garbage that is not a real PDF body\n%%EOF\n");

    expect($issue->processing_status)->toBe('failed')
        ->and($issue->processing_error)->toBe('pdf_unreadable')
        ->and($issue->pages)->toHaveCount(0);
})->skip(fn (): bool => (new finfo(FILEINFO_MIME_TYPE))->buffer("%PDF-1.4\n%garbage\n%%EOF\n") !== 'application/pdf', 'libmagic does not call this a PDF');

it('records a crashed job on the issue', function (): void {
    $issue = IssueModel::query()->findOrFail(app(IssueUploader::class)->upload(
        new IssueInput(121, '2026-10-15'),
        (function (): string {
            $file = (string) tempnam(sys_get_temp_dir(), 'elaf-test-');
            file_put_contents($file, DemoPdf::blank(1));

            return $file;
        })(),
        null,
    )->value);
    $issue->forceFill(['processing_status' => 'processing'])->save();

    (new ProcessIssuePdfJob($issue->id))->failed(new RuntimeException('worker killed'));

    expect($issue->refresh()->processing_status)->toBe('failed')
        ->and($issue->processing_error)->toBe('job_crashed');
});
