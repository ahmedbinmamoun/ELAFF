<?php

declare(strict_types=1);

namespace Elaf\Engagement\Infrastructure\Persistence;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $title
 * @property string $body
 * @property string $status
 * @property string|null $moderator_note
 * @property int|null $moderated_by
 * @property int|null $article_id
 * @property string $ip_hash
 * @property CarbonImmutable $received_at
 */
final class ReaderLetterModel extends Model
{
    protected $table = 'reader_letters';

    protected $guarded = [];

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'moderated_by' => 'integer',
            'article_id' => 'integer',
            'received_at' => 'immutable_datetime',
        ];
    }
}
