<?php

declare(strict_types=1);

namespace Elaf\Publishing\Infrastructure\Processing;

use Elaf\Publishing\Application\Port\ImageOptimizer;
use Elaf\Publishing\Application\Port\ImageSize;
use Elaf\Publishing\Application\Port\IssueProcessingError;
use Illuminate\Contracts\Filesystem\Filesystem;
use Imagick;
use ImagickException;
use ImagickPixel;

/**
 * WebP pages and the social card with Imagick (PRD §5.4).
 */
final readonly class ImagickImageOptimizer implements ImageOptimizer
{
    private const SOCIAL_WIDTH = 1200;

    private const SOCIAL_HEIGHT = 630;

    /** Brand navy (tailwind.config.js brand.DEFAULT) behind the cover. */
    private const SOCIAL_BACKGROUND = '#0e2a47';

    public function __construct(
        private Filesystem $disk,
        private int $quality = 78,
    ) {}

    public function webp(string $source, string $targetPath, int $maxWidth): ImageSize
    {
        $image = null;

        try {
            $image = new Imagick($source);
            $image->stripImage();

            if ($image->getImageWidth() > $maxWidth) {
                $image->resizeImage($maxWidth, 0, Imagick::FILTER_LANCZOS, 1);
            }

            $image->setImageFormat('webp');
            $image->setImageCompressionQuality($this->quality);
            $image->setOption('webp:method', '4');
            $this->put($targetPath, $image->getImageBlob());

            return new ImageSize($image->getImageWidth(), $image->getImageHeight());
        } catch (ImagickException $e) {
            throw new IssueProcessingError('image_failed', 'A page could not be converted to WebP.', $e);
        } finally {
            $image?->clear();
        }
    }

    public function socialCard(string $source, string $targetPath): void
    {
        $page = null;
        $card = null;

        try {
            $page = new Imagick($source);
            $page->stripImage();
            $page->thumbnailImage(self::SOCIAL_WIDTH - 80, self::SOCIAL_HEIGHT - 60, true);

            $card = new Imagick;
            $card->newImage(self::SOCIAL_WIDTH, self::SOCIAL_HEIGHT, new ImagickPixel(self::SOCIAL_BACKGROUND));
            $card->compositeImage(
                $page,
                Imagick::COMPOSITE_OVER,
                intdiv(self::SOCIAL_WIDTH - $page->getImageWidth(), 2),
                intdiv(self::SOCIAL_HEIGHT - $page->getImageHeight(), 2),
            );
            $card->setImageFormat('jpeg');
            $card->setImageCompressionQuality(82);
            $this->put($targetPath, $card->getImageBlob());
        } catch (ImagickException $e) {
            throw new IssueProcessingError('image_failed', 'The social image could not be made.', $e);
        } finally {
            $page?->clear();
            $card?->clear();
        }
    }

    private function put(string $path, string $contents): void
    {
        if (! $this->disk->put($path, $contents)) {
            throw new IssueProcessingError('image_failed', sprintf('Cannot write %s.', $path));
        }
    }
}
