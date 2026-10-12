<?php

declare(strict_types=1);

namespace Elaf\Publishing\Infrastructure\Files;

use Elaf\Publishing\Application\Port\IssueFiles;
use Elaf\Publishing\Application\Port\LocalFile;
use finfo;
use Illuminate\Contracts\Filesystem\Filesystem;
use RuntimeException;

/**
 * Issue files on a Laravel disk (the public disk by default: PDFs and page
 * images are meant to be downloaded by anyone, PRD §9).
 */
final readonly class DiskIssueFiles implements IssueFiles
{
    public function __construct(private Filesystem $disk) {}

    /**
     * PDF when the content starts with "%PDF-" and libmagic agrees.
     */
    public function inspect(string $localPath): LocalFile
    {
        if (! is_file($localPath) || ! is_readable($localPath)) {
            return new LocalFile(false, 0, str_repeat('0', 64));
        }

        $handle = fopen($localPath, 'rb');
        $head = $handle === false ? '' : (string) fread($handle, 1024);

        if ($handle !== false) {
            fclose($handle);
        }

        $isPdf = str_contains($head, '%PDF-')
            && (new finfo(FILEINFO_MIME_TYPE))->file($localPath) === 'application/pdf';

        return new LocalFile($isPdf, (int) filesize($localPath), (string) hash_file('sha256', $localPath));
    }

    public function storePdf(string $localPath, string $targetPath): void
    {
        $stream = fopen($localPath, 'rb');

        if ($stream === false) {
            throw new RuntimeException('Cannot read the uploaded PDF.');
        }

        try {
            if (! $this->disk->writeStream($targetPath, $stream)) {
                throw new RuntimeException(sprintf('Cannot store the PDF at %s.', $targetPath));
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function deleteDirectory(string $directory): void
    {
        if ($directory !== '' && $directory !== '.' && $this->disk->directoryExists($directory)) {
            $this->disk->deleteDirectory($directory);
        }
    }
}
