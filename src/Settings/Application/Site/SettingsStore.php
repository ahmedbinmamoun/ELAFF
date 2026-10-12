<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Site;

/**
 * Write side of the key-value settings table (PRD §7 `settings`).
 */
interface SettingsStore
{
    /**
     * @return array<string, mixed> stored values only, by key
     */
    public function all(): array;

    /**
     * @param  array<string, string|bool|null>  $values  upserted by key
     */
    public function put(array $values): void;
}
