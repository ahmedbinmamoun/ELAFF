<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 */
final class TagModel extends Model
{
    protected $table = 'tags';

    protected $guarded = [];
}
