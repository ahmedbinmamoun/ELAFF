<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Publishing\Application\Port\IssueProcessingError;
use Elaf\Publishing\Application\Port\PdfRasterizer;
use Elaf\Publishing\Application\Port\RasterizedPages;

final class FakePdfRasterizer implements PdfRasterizer
{
    public int $pages = 3;

    public ?string $failWith = null;

    /** @var list<string> */
    public array $rasterized = [];

    public int $discarded = 0;

    public function rasterize(string $pdfPath): RasterizedPages
    {
        if ($this->failWith !== null) {
            throw new IssueProcessingError($this->failWith, 'Fake failure.');
        }

        $this->rasterized[] = $pdfPath;

        return new RasterizedPages('/tmp/fake', array_map(static fn (int $n): string => "/tmp/fake/page-{$n}.png", $this->pages > 0 ? range(1, $this->pages) : []));
    }

    public function discard(RasterizedPages $pages): void
    {
        $this->discarded++;
    }
}
