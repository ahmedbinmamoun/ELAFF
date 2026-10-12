<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Port;

/**
 * Turns rendered pages into web images (PRD §4: WebP, known dimensions).
 * Sources are local files; targets are paths on the issue storage disk.
 */
interface ImageOptimizer
{
    /**
     * Writes $source as WebP, scaled down to at most $maxWidth pixels wide.
     *
     * @throws IssueProcessingError
     */
    public function webp(string $source, string $targetPath, int $maxWidth): ImageSize;

    /**
     * A 1200×630 JPEG for social sharing, the whole page fitted on a plain
     * background.
     *
     * @throws IssueProcessingError
     */
    public function socialCard(string $source, string $targetPath): void;
}
