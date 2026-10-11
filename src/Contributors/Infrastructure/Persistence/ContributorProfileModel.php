<?php

declare(strict_types=1);

namespace Elaf\Contributors\Infrastructure\Persistence;

use App\Models\User;
use Carbon\CarbonImmutable;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $author_id
 * @property string|null $phone
 * @property string $bio
 * @property string|null $photo_path
 * @property CarbonImmutable $terms_accepted_at
 * @property int|null $approved_by
 * @property CarbonImmutable|null $approved_at
 * @property string|null $rejection_reason
 * @property string|null $requested_name
 * @property-read User|null $user
 * @property-read AuthorModel|null $author
 */
final class ContributorProfileModel extends Model
{
    protected $table = 'contributor_profiles';

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<AuthorModel, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(AuthorModel::class, 'author_id');
    }

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'author_id' => 'integer',
            'approved_by' => 'integer',
            'terms_accepted_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
        ];
    }
}
