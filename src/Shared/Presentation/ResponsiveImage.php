<?php

declare(strict_types=1);

namespace Elaf\Shared\Presentation;

use Illuminate\Support\Facades\Storage;

/**
 * <img> attributes for an uploaded image. When WebP width derivatives exist
 * next to the original ("name-320.webp", "name-640.webp", …, produced by the
 * image pipeline of Phase 6) a srcset is emitted; otherwise the original only.
 */
final class ResponsiveImage
{
    private const WIDTHS = [160, 320, 640, 960, 1280];

    /**
     * @return array{src: string, srcset: string|null}
     */
    public static function sources(string $path): array
    {
        $base = preg_replace('/\.[A-Za-z0-9]+$/', '', $path) ?? $path;
        $disk = Storage::disk(PublicMedia::DISK);
        $candidates = [];

        foreach (self::WIDTHS as $width) {
            $variant = "{$base}-{$width}.webp";

            if ($disk->exists($variant)) {
                $candidates[] = PublicMedia::url($variant)." {$width}w";
            }
        }

        return [
            'src' => PublicMedia::url($path),
            'srcset' => $candidates === [] ? null : implode(', ', $candidates),
        ];
    }
}
