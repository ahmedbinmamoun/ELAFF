<?php

declare(strict_types=1);

namespace Elaf\Publishing\Domain\Issue;

use Elaf\Publishing\Domain\Issue\Exception\InvalidIssue;

/**
 * The stored PDF of an issue (PRD §6.2). $checksum is the SHA-256 of the
 * content; it names the storage folder, so a replaced PDF never reuses a URL.
 */
final readonly class IssueFile
{
    /** PRD §11: issue PDFs up to 150 MB. */
    public const MAX_BYTES = 150 * 1024 * 1024;

    public function __construct(
        public string $path,
        public int $size,
        public string $checksum,
    ) {
        if (trim($path) === '') {
            throw InvalidIssue::because('file_missing', 'Issue file path must not be empty.');
        }

        if ($size < 1 || $size > self::MAX_BYTES) {
            throw InvalidIssue::because('file_size', sprintf('Issue file size %d is outside 1..%d bytes.', $size, self::MAX_BYTES));
        }

        if (preg_match('/^[0-9a-f]{64}$/', $checksum) !== 1) {
            throw InvalidIssue::because('file_checksum', 'Issue file checksum must be a SHA-256 hex digest.');
        }
    }

    public function equals(self $other): bool
    {
        return $other->checksum === $this->checksum && $other->path === $this->path;
    }
}
