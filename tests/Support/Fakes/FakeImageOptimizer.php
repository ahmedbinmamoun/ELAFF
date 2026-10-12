<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Publishing\Application\Port\ImageOptimizer;
use Elaf\Publishing\Application\Port\ImageSize;
use Elaf\Publishing\Application\Port\IssueProcessingError;

final class FakeImageOptimizer implements ImageOptimizer
{
    /** @var array<string, int> target => max width */
    public array $written = [];

    public ?string $socialCard = null;

    public bool $fail = false;

    public function webp(string $source, string $targetPath, int $maxWidth): ImageSize
    {
        if ($this->fail) {
            throw new IssueProcessingError('image_failed', 'Fake failure.');
        }

        $this->written[$targetPath] = $maxWidth;

        return new ImageSize($maxWidth, (int) round($maxWidth * 1.414));
    }

    public function socialCard(string $source, string $targetPath): void
    {
        $this->socialCard = $targetPath;
    }
}
