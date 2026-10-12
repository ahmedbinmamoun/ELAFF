<?php

declare(strict_types=1);

namespace Elaf\Publishing\Application\Port;

/**
 * Renders PDF pages to images (PRD §11 step 4).
 */
interface PdfRasterizer
{
    /**
     * Renders every page of a stored PDF into a fresh temporary folder.
     * The caller must discard() the result.
     *
     * @throws IssueProcessingError
     */
    public function rasterize(string $pdfPath): RasterizedPages;

    public function discard(RasterizedPages $pages): void;
}
