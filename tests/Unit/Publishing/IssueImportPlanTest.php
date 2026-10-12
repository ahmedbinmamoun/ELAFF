<?php

declare(strict_types=1);

use Elaf\Publishing\Application\Import\IssueFileName;
use Elaf\Publishing\Application\Import\IssueImportItem;
use Elaf\Publishing\Application\Import\IssueImportPlanner;

it('reads the date from archive file names (FR-ISS-05)', function (string $name, ?string $date): void {
    expect(IssueFileName::date($name)?->value)->toBe($date);
})->with([
    ['إيلاف_8_10_2026.pdf', '2026-10-08'],
    ['/archive/2026/إيلاف_08_10_2026.PDF', '2026-10-08'],
    ['إيلاف_٢٤_سبتمبر_٢٠٢٦.pdf', '2026-09-24'],
    ['أيلاف 1-أكتوبر-2025.pdf', '2025-10-01'],
    ['ايلاف_3_ابريل_2024.pdf', '2024-04-03'],
    ['إيلاف_15_يونيه_2023.pdf', '2023-06-15'],
    ['elaf_5_1_2022.pdf', '2022-01-05'],
    ['إيلاف_31_2_2026.pdf', null],
    ['إيلاف_1_13_2026.pdf', null],
    ['إيلاف_1_فلان_2026.pdf', null],
    ['issue-120.pdf', null],
    ['إيلاف_8_10_2026.docx', null],
]);

it('numbers files in date order from a first number, reporting the rest', function (): void {
    $plan = IssueImportPlanner::byFileName([
        '/in/إيلاف_8_10_2026.pdf',
        '/in/إيلاف_24_9_2026.pdf',
        '/in/scan.pdf',
        '/in/إيلاف_1_10_2026.pdf',
        '/in/ايلاف_1_اكتوبر_2026.pdf',
    ], 118);

    expect(array_map(static fn (IssueImportItem $i): array => [basename($i->path), $i->input->number, $i->input->publishedOn], $plan->items))->toBe([
        ['إيلاف_24_9_2026.pdf', 118, '2026-09-24'],
        ['إيلاف_1_10_2026.pdf', 119, '2026-10-01'],
        ['إيلاف_8_10_2026.pdf', 120, '2026-10-08'],
    ])->and($plan->problems)->toBe(['scan.pdf' => 'unparsed_name', 'ايلاف_1_اكتوبر_2026.pdf' => 'duplicate_date']);
});

it('takes numbers, dates and titles from a CSV map', function (): void {
    $plan = IssueImportPlanner::byMap(['/in/a.pdf', '/in/إيلاف_1_10_2026.pdf', '/in/unknown.pdf', '/in/b.pdf'], [
        'a.pdf' => ['number' => 120, 'date' => '2026-10-08', 'title' => 'عدد خاص'],
        'إيلاف_1_10_2026.pdf' => ['number' => 119, 'date' => null, 'title' => null],
        'b.pdf' => ['number' => 50, 'date' => null, 'title' => null],
    ]);

    expect(array_map(static fn (IssueImportItem $i): array => [$i->input->number, $i->input->publishedOn, $i->input->title], $plan->items))->toBe([
        [119, '2026-10-01', null],
        [120, '2026-10-08', 'عدد خاص'],
    ])->and($plan->problems)->toBe(['unknown.pdf' => 'not_in_map', 'b.pdf' => 'unparsed_name']);
});
