<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Settings\Application\Site\SettingsReader;
use Elaf\Settings\Application\Site\SettingsStore;
use Elaf\Settings\Application\Site\SiteSettings;

/**
 * Settings store and reader in one: what is put is read back over defaults.
 */
final class InMemorySettings implements SettingsReader, SettingsStore
{
    /** @var array<string, mixed> */
    public array $stored = [];

    public function __construct(private readonly SiteSettings $defaults = new SiteSettings('إيلاف')) {}

    public function site(): SiteSettings
    {
        return SiteSettings::fromArray($this->stored, $this->defaults);
    }

    public function all(): array
    {
        return $this->stored;
    }

    public function put(array $values): void
    {
        $this->stored = [...$this->stored, ...$values];
    }
}
