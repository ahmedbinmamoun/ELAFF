<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Import;

use Elaf\Publishing\Application\Issue\IssueInput;

/**
 * Decides number and date for each PDF of an archive folder (FR-ISS-05).
 * Two sources: a CSV map (file name → number, date, title), or the date in
 * the file name with numbers counted up from a first number in date order.
 */
final class IssueImportPlanner
{
    /**
     * @param  list<string>  $paths
     */
    public static function byFileName(array $paths, int $firstNumber): IssueImportPlan
    {
        $dated = [];
        $problems = [];

        foreach ($paths as $path) {
            $date = IssueFileName::date($path);

            if ($date === null) {
                $problems[basename($path)] = 'unparsed_name';
            } elseif (isset($dated[$date->value])) {
                $problems[basename($path)] = 'duplicate_date';
            } else {
                $dated[$date->value] = $path;
            }
        }

        ksort($dated);
        $items = [];
        $number = $firstNumber;

        foreach ($dated as $date => $path) {
            $items[] = new IssueImportItem($path, new IssueInput($number++, (string) $date));
        }

        return new IssueImportPlan($items, $problems);
    }

    /**
     * Files missing from the map are reported, not guessed. A row without a
     * date falls back to the date in the file name.
     *
     * @param  list<string>  $paths
     * @param  array<string, array{number: int, date: string|null, title: string|null}>  $map  keyed by file name
     */
    public static function byMap(array $paths, array $map): IssueImportPlan
    {
        $items = [];
        $problems = [];

        foreach ($paths as $path) {
            $row = $map[basename($path)] ?? null;

            if ($row === null) {
                $problems[basename($path)] = 'not_in_map';

                continue;
            }

            $date = $row['date'] ?? IssueFileName::date($path)?->value;

            if ($date === null) {
                $problems[basename($path)] = 'unparsed_name';

                continue;
            }

            $items[] = new IssueImportItem($path, new IssueInput($row['number'], $date, $row['title']));
        }

        usort($items, static fn (IssueImportItem $a, IssueImportItem $b): int => $a->input->number <=> $b->input->number);

        return new IssueImportPlan($items, $problems);
    }
}
