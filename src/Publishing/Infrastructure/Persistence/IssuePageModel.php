<?php

declare(strict_types=1);

namespace Elaf\Publishing\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $issue_id
 * @property int $page_number
 * @property string $image_path
 * @property string $thumb_path
 * @property int $width
 * @property int $height
 */
final class IssuePageModel extends Model
{
    protected $table = 'issue_pages';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issue_id' => 'integer',
            'page_number' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }
}
