<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Port;

/**
 * File storage for issues (the FileStorage port of PRD §5.7, narrowed to what
 * issues need). Paths are relative to the issue storage disk.
 */
interface IssueFiles
{
    /**
     * Looks at a local file before it is stored. The type is decided by the
     * content (magic bytes), never by the file name (PRD §11).
     */
    public function inspect(string $localPath): LocalFile;

    public function storePdf(string $localPath, string $targetPath): void;

    /**
     * Removes a folder and everything in it. A missing folder is not an error.
     */
    public function deleteDirectory(string $directory): void;
}
