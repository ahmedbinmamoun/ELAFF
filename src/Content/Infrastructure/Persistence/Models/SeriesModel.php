<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 */
final class SeriesModel extends Model
{
    protected $table = 'series';

    protected $guarded = [];
}
