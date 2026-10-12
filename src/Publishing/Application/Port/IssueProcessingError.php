<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Port;

use RuntimeException;
use Throwable;

/**
 * Rendering failed. $reason is a short key the admin translates
 * (FR-ISS-06: pdf_missing, pdf_unreadable, timeout, tool_missing,
 * image_failed, no_pages); technical details stay in the message and the log.
 */
final class IssueProcessingError extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
