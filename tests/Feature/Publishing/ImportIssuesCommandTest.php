<?php

declare(strict_types=1);

use Database\Factories\Support\DemoPdf;
use Elaf\Publishing\Infrastructure\Persistence\IssueModel;
use Elaf\Publishing\Infrastructure\Processing\ProcessIssuePdfJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\IssueFixtures;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('public');
    Queue::fake();
    $this->dir = sys_get_temp_dir().'/elaf-import-'.bin2hex(random_bytes(4));
    mkdir($this->dir);
    file_put_contents($this->dir.'/إيلاف_1_10_2026.pdf', DemoPdf::blank(1));
    file_put_contents($this->dir.'/إيلاف_٢٤_سبتمبر_٢٠٢٦.pdf', DemoPdf::blank(1));
    file_put_contents($this->dir.'/إيلاف_8_10_2026.pdf', 'not a pdf at all');
    file_put_contents($this->dir.'/notes.pdf', DemoPdf::blank(1));
    file_put_contents($this->dir.'/readme.txt', 'x');
});

afterEach(function (): void {
    array_map(unlink(...), glob($this->dir.'/*') ?: []);
    rmdir($this->dir);
});

it('shows the plan without importing on a dry run', function (): void {
    $this->artisan('issues:import', ['directory' => $this->dir, '--first-number' => 118, '--dry-run' => true])
        ->expectsOutputToContain('Skipped notes.pdf: unparsed_name')
        ->expectsOutputToContain('Dry run: 3 of 4 files would be imported.')
        ->assertSuccessful();

    expect(IssueModel::query()->count())->toBe(0);
});

it('imports from file names in date order and is safe to run again (FR-ISS-05)', function (): void {
    IssueFixtures::ready(119, '2026-10-01');

    $this->artisan('issues:import', ['directory' => $this->dir, '--first-number' => 118])
        ->expectsOutputToContain('Imported إيلاف_٢٤_سبتمبر_٢٠٢٦.pdf as issue 118')
        ->expectsOutputToContain('Issue 119 already exists')
        ->expectsOutputToContain('إيلاف_8_10_2026.pdf not imported: not_pdf')
        ->assertFailed();

    expect(IssueModel::query()->orderBy('number')->pluck('published_on', 'number')->map(fn ($d) => $d->format('Y-m-d'))->all())
        ->toBe([118 => '2026-09-24', 119 => '2026-10-01']);
    Queue::assertPushed(ProcessIssuePdfJob::class, 1);
});

it('imports from a CSV map with titles', function (): void {
    file_put_contents($this->dir.'/map.csv', "\xEF\xBB\xBFfile,number,date,title\nnotes.pdf,77,2020-05-07,عدد قديم\nإيلاف_1_10_2026.pdf,119,,\n");

    $this->artisan('issues:import', ['directory' => $this->dir, '--csv' => $this->dir.'/map.csv'])->assertSuccessful();

    expect(IssueModel::query()->orderBy('number')->get(['number', 'title'])->toArray())->toBe([
        ['number' => 77, 'title' => 'عدد قديم'],
        ['number' => 119, 'title' => null],
    ]);
});

it('refuses to guess numbers', function (): void {
    $this->artisan('issues:import', ['directory' => $this->dir])->assertFailed();
    $this->artisan('issues:import', ['directory' => '/no/such/folder', '--first-number' => 1])->assertFailed();
});
