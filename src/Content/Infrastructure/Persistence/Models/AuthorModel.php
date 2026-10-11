<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $name
 * @property string $slug
 * @property string|null $title
 * @property string|null $bio
 * @property string|null $photo_path
 * @property bool $is_columnist
 * @property array<string, string>|null $social_links
 */
final class AuthorModel extends Model
{
    use SoftDeletes;

    protected $table = 'authors';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'is_columnist' => 'boolean',
            'social_links' => 'array',
        ];
    }
}
