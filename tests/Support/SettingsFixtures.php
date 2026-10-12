<?php

declare(strict_types=1);

namespace Tests\Support;

use Elaf\Settings\Application\Site\SettingsStore;
use Elaf\Settings\Infrastructure\CachedSettingsReader;

/**
 * Stores settings the way the admin does and drops the cached copy.
 */
final class SettingsFixtures
{
    /**
     * @param  array<string, string|bool|null>  $values  by settings key
     */
    public static function set(array $values): void
    {
        app(SettingsStore::class)->put($values);
        app(CachedSettingsReader::class)->forget();
    }
}
