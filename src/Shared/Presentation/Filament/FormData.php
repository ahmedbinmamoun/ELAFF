<?php

declare(strict_types=1);

namespace Elaf\Shared\Presentation\Filament;

use Illuminate\Database\Eloquent\Model;

/**
 * Typed reads of Filament form state (array<string, mixed>) for building
 * use-case inputs.
 */
final class FormData
{
    /** @param array<array-key, mixed> $data */
    public static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Blank becomes null.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function text(array $data, string $key): ?string
    {
        $value = self::string($data, $key);

        return trim($value) === '' ? null : $value;
    }

    /** @param array<array-key, mixed> $data */
    public static function int(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    /** @param array<array-key, mixed> $data */
    public static function bool(array $data, string $key, bool $default = false): bool
    {
        return array_key_exists($key, $data) ? (bool) $data[$key] : $default;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return list<int>
     */
    public static function ints(array $data, string $key): array
    {
        $values = $data[$key] ?? [];

        return is_array($values) ? array_values(array_map(intval(...), array_filter($values, is_numeric(...)))) : [];
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return list<string>
     */
    public static function strings(array $data, string $key): array
    {
        $values = $data[$key] ?? [];

        return is_array($values) ? array_values(array_filter($values, is_string(...))) : [];
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<string, string>
     */
    public static function stringMap(array $data, string $key): array
    {
        $values = $data[$key] ?? [];
        $map = [];

        foreach (is_array($values) ? $values : [] as $k => $v) {
            if (is_scalar($v)) {
                $map[(string) $k] = (string) $v;
            }
        }

        return $map;
    }

    public static function key(Model $record): int
    {
        $key = $record->getKey();

        return is_numeric($key) ? (int) $key : 0;
    }

    public static function actorId(): int
    {
        $id = auth()->id();

        return is_numeric($id) ? (int) $id : 0;
    }
}
