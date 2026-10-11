<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Homepage\Blocks;

final class BlockOptions
{
    /**
     * @param  array<string, mixed>  $options
     */
    public static function int(array $options, string $key, int $default, int $max = 50): int
    {
        $value = $options[$key] ?? $default;

        return max(0, min($max, is_numeric($value) ? (int) $value : $default));
    }
}
