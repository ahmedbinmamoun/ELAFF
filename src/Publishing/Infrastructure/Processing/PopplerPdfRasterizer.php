<?php

declare(strict_types=1);

namespace Elaf\Publishing\Infrastructure\Processing;

use Elaf\Publishing\Application\Port\IssueProcessingError;
use Elaf\Publishing\Application\Port\PdfRasterizer;
use Elaf\Publishing\Application\Port\RasterizedPages;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\Filesystem as LocalFiles;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException as ProcessFailure;
use Symfony\Component\Process\Process;

/**
 * PRD §11 step 4: poppler's pdftoppm renders each page to PNG at ~110 DPI.
 * Arguments are passed as an array (no shell), with a time limit.
 */
final readonly class PopplerPdfRasterizer implements PdfRasterizer
{
    public function __construct(
        private Filesystem $disk,
        private LocalFiles $files,
        private LoggerInterface $log,
        private int $dpi = 110,
        private int $timeoutSeconds = 240,
        private string $binary = 'pdftoppm',
    ) {}

    public function rasterize(string $pdfPath): RasterizedPages
    {
        $source = $this->disk->path($pdfPath);

        if (! is_file($source)) {
            throw new IssueProcessingError('pdf_missing', sprintf('PDF %s is missing.', $pdfPath));
        }

        $directory = sys_get_temp_dir().'/elaf-issue-'.bin2hex(random_bytes(8));
        $this->files->ensureDirectoryExists($directory, 0700);
        $process = new Process([$this->binary, '-r', (string) $this->dpi, '-png', $source, $directory.'/page'], timeout: $this->timeoutSeconds);

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            $this->files->deleteDirectory($directory);

            throw new IssueProcessingError('timeout', sprintf('pdftoppm took longer than %d s.', $this->timeoutSeconds), $e);
        } catch (ProcessFailure $e) {
            $this->files->deleteDirectory($directory);

            throw new IssueProcessingError('tool_missing', 'pdftoppm could not be started.', $e);
        }

        if (! $process->isSuccessful()) {
            $this->files->deleteDirectory($directory);
            $this->log->warning('pdftoppm failed', ['pdf' => $pdfPath, 'exit' => $process->getExitCode(), 'stderr' => mb_substr($process->getErrorOutput(), 0, 2000)]);

            throw new IssueProcessingError($process->getExitCode() === 127 ? 'tool_missing' : 'pdf_unreadable', sprintf('pdftoppm exited with %s.', (string) $process->getExitCode()));
        }

        $images = glob($directory.'/page-*.png') ?: [];
        natsort($images);

        return new RasterizedPages($directory, array_values($images));
    }

    public function discard(RasterizedPages $pages): void
    {
        if (str_starts_with($pages->directory, sys_get_temp_dir().'/elaf-issue-')) {
            $this->files->deleteDirectory($pages->directory);
        }
    }
}
