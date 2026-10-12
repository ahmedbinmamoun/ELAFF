<?php

declare(strict_types=1);

namespace Elaf\Publishing\Presentation\Http;

use Elaf\Publishing\Infrastructure\PublishingServiceProvider;
use Elaf\Shared\Presentation\PublicMedia;

/**
 * URLs of issue files: relative /storage/… on the public disk (default),
 * the disk's own URL otherwise.
 */
final class IssueMedia
{
    public static function url(string $path): string
    {
        $disk = config('elaf.issues.disk', PublicMedia::DISK);

        return $disk === PublicMedia::DISK ? PublicMedia::url($path) : PublishingServiceProvider::disk()->url($path);
    }
}
