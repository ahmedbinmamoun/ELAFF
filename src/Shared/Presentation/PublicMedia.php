<?php

declare(strict_types=1);

namespace Elaf\Shared\Presentation;

/**
 * Uploaded files on the "public" disk are served under /storage (relative,
 * so stored HTML does not depend on the host name).
 */
final class PublicMedia
{
    public const DISK = 'public';

    private const PREFIX = '/storage/';

    public static function url(string $path): string
    {
        return self::PREFIX.ltrim($path, '/');
    }

    public static function pathFromUrl(string $url): ?string
    {
        return str_starts_with($url, self::PREFIX) ? substr($url, strlen(self::PREFIX)) : null;
    }
}
