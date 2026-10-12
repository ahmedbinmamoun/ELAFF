<?php

declare(strict_types=1);

namespace Elaf\Settings\Infrastructure\Persistence;

use Elaf\Settings\Application\Site\SettingsStore;

final class EloquentSettingsStore implements SettingsStore
{
    public function all(): array
    {
        $values = [];

        foreach (SettingModel::query()->get(['key', 'value']) as $setting) {
            $values[$setting->key] = $setting->value;
        }

        return $values;
    }

    public function put(array $values): void
    {
        foreach ($values as $key => $value) {
            SettingModel::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
