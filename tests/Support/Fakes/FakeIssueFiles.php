<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Publishing\Application\Port\IssueFiles;
use Elaf\Publishing\Application\Port\LocalFile;

final class FakeIssueFiles implements IssueFiles
{
    /** @var array<string, LocalFile> local path => what inspect() reports */
    private array $local = [];

    /** @var array<string, string> stored path => local source */
    public array $stored = [];

    /** @var list<string> */
    public array $deletedDirectories = [];

    /**
     * Registers a local PDF; the checksum derives from $content.
     */
    public function pdf(string $localPath, string $content = 'pdf', int $size = 1000): string
    {
        $this->local[$localPath] = new LocalFile(true, $size, hash('sha256', $content));

        return $localPath;
    }

    public function notPdf(string $localPath): string
    {
        $this->local[$localPath] = new LocalFile(false, 10, hash('sha256', $localPath));

        return $localPath;
    }

    public function inspect(string $localPath): LocalFile
    {
        return $this->local[$localPath] ?? new LocalFile(false, 0, str_repeat('0', 64));
    }

    public function storePdf(string $localPath, string $targetPath): void
    {
        $this->stored[$targetPath] = $localPath;
    }

    public function deleteDirectory(string $directory): void
    {
        $this->deletedDirectories[] = $directory;

        foreach (array_keys($this->stored) as $path) {
            if (str_starts_with($path, $directory.'/')) {
                unset($this->stored[$path]);
            }
        }
    }
}
