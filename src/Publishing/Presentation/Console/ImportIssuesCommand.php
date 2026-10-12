<?php

declare(strict_types=1);

namespace Elaf\Publishing\Presentation\Console;

use Elaf\Publishing\Application\Import\ImportIssuesFromDirectoryHandler;
use Elaf\Publishing\Application\Import\IssueImportPlan;
use Elaf\Publishing\Application\Import\IssueImportPlanner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use SplFileObject;

/**
 * FR-ISS-05: back-fills the archive from a folder of PDFs. Number and date
 * come from a CSV map (file,number,date[,title]) or from the file names
 * ("إيلاف_{day}_{month}_{year}.pdf") counted up from --first-number in date
 * order. Each file becomes a pending issue rendered by the queue.
 */
#[Signature('issues:import
    {directory : Folder holding the PDFs (not recursive)}
    {--csv= : CSV map with the columns file,number,date[,title]}
    {--first-number= : Number of the oldest issue when numbering from file names}
    {--dry-run : Show the plan without importing}')]
#[Description('Import print issue PDFs from a folder')]
final class ImportIssuesCommand extends Command
{
    public function handle(ImportIssuesFromDirectoryHandler $handler): int
    {
        $directory = rtrim($this->stringArgument('directory'), '/');

        if (! is_dir($directory)) {
            $this->error(sprintf('Folder %s does not exist.', $directory));

            return self::FAILURE;
        }

        $paths = $this->pdfs($directory);
        $plan = $this->plan($paths);

        if ($plan === null) {
            return self::FAILURE;
        }

        $this->table(['File', 'Number', 'Date'], array_map(
            static fn ($item): array => [basename($item->path), $item->input->number, $item->input->publishedOn],
            $plan->items,
        ));

        foreach ($plan->problems as $file => $reason) {
            $this->warn(sprintf('Skipped %s: %s', $file, $reason));
        }

        if ($this->option('dry-run')) {
            $this->info(sprintf('Dry run: %d of %d files would be imported.', count($plan->items), count($paths)));

            return self::SUCCESS;
        }

        $report = $handler->handle($plan);

        foreach ($report->imported as $file => $number) {
            $this->info(sprintf('Imported %s as issue %d (rendering queued).', $file, $number));
        }

        foreach ($report->skipped as $file => $number) {
            $this->line(sprintf('Issue %d already exists, %s skipped.', $number, $file));
        }

        foreach ($report->failed as $file => $reason) {
            $this->error(sprintf('%s not imported: %s', $file, $reason));
        }

        $this->info(sprintf('%d imported, %d already present, %d failed.', count($report->imported), count($report->skipped), count($report->failed)));

        return $report->failed === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  list<string>  $paths
     */
    private function plan(array $paths): ?IssueImportPlan
    {
        $csv = $this->option('csv');

        if (is_string($csv) && $csv !== '') {
            $map = $this->readMap($csv);

            return $map === null ? null : IssueImportPlanner::byMap($paths, $map);
        }

        $first = $this->option('first-number');
        $first = is_scalar($first) ? (string) $first : '';

        if (! ctype_digit($first) || (int) $first < 1) {
            $this->error('Give --csv=map.csv, or --first-number=N to number the issues from their file names.');

            return null;
        }

        return IssueImportPlanner::byFileName($paths, (int) $first);
    }

    /**
     * @return list<string>
     */
    private function pdfs(string $directory): array
    {
        $paths = array_values(array_filter(
            glob($directory.'/*') ?: [],
            static fn (string $path): bool => is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf',
        ));
        sort($paths);

        return $paths;
    }

    /**
     * @return array<string, array{number: int, date: string|null, title: string|null}>|null
     */
    private function readMap(string $path): ?array
    {
        if (! is_file($path)) {
            $this->error(sprintf('CSV file %s does not exist.', $path));

            return null;
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);
        $map = [];

        foreach ($file as $line => $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            $cells = array_map(static fn (mixed $cell): string => trim(is_scalar($cell) ? (string) $cell : ''), $row);
            $name = preg_replace('/^\xEF\xBB\xBF/', '', $cells[0] ?? '') ?? '';

            if ($line === 0 && strtolower($name) === 'file') {
                continue;
            }

            if ($name === '' || ! ctype_digit($cells[1] ?? '')) {
                $this->warn(sprintf('CSV line %d ignored: expected file,number,date[,title].', (int) $line + 1));

                continue;
            }

            $map[$name] = [
                'number' => (int) $cells[1],
                'date' => ($cells[2] ?? '') === '' ? null : $cells[2],
                'title' => ($cells[3] ?? '') === '' ? null : $cells[3],
            ];
        }

        return $map;
    }

    private function stringArgument(string $name): string
    {
        $value = $this->argument($name);

        return is_string($value) ? $value : '';
    }
}
