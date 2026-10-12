<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Issue;

use Elaf\Publishing\Application\Port\IssueFiles;
use Elaf\Publishing\Domain\Issue\IssueFile;
use Elaf\Publishing\Domain\Issue\IssueNumber;
use Elaf\Shared\Application\Validation\InvalidInput;

/**
 * Checks an uploaded PDF and copies it to its place (PRD §11 steps 1–2).
 */
final readonly class IssuePdfStore
{
    public function __construct(private IssueFiles $files) {}

    public function store(string $localPath, IssueNumber $number): IssueFile
    {
        $file = $this->files->inspect($localPath);

        if (! $file->isPdf) {
            throw InvalidInput::because('pdf', 'not_pdf', 'The uploaded file is not a PDF.');
        }

        if ($file->size > IssueFile::MAX_BYTES) {
            throw InvalidInput::because('pdf', 'file_too_large', 'The PDF is larger than allowed.', ['max' => intdiv(IssueFile::MAX_BYTES, 1024 * 1024)]);
        }

        $path = IssueStorageLayout::pdf($number, $file->checksum);
        $this->files->storePdf($localPath, $path);

        return new IssueFile($path, $file->size, $file->checksum);
    }

    public function discard(IssueFile $file): void
    {
        $this->files->deleteDirectory(IssueStorageLayout::directory($file));
    }
}
