<?php

declare(strict_types=1);

namespace Elaf\Contributors\Infrastructure\Persistence;

use App\Models\User;
use Carbon\CarbonImmutable;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Infrastructure\Persistence\Eloquent\LocalTime;
use Elaf\Contributors\Domain\Contributor;
use Elaf\Contributors\Domain\ContributorProfile;
use Elaf\Contributors\Domain\ContributorRepository;
use Elaf\Contributors\Domain\ContributorStatus;
use Elaf\Contributors\Domain\DisplayName;
use Elaf\Contributors\Domain\Exception\ContributorNotFound;
use Elaf\Identity\Domain\UserId;
use Illuminate\Database\ConnectionInterface;
use LogicException;

/**
 * Name and status live on the users row (sign-in reads them); the rest in
 * contributor_profiles.
 */
final readonly class EloquentContributorRepository implements ContributorRepository
{
    public function __construct(private ConnectionInterface $db) {}

    public function get(UserId $id): Contributor
    {
        $user = User::query()->where('type', 'contributor')->find($id->value);
        $profile = ContributorProfileModel::query()->where('user_id', $id->value)->first();

        if ($user === null || $profile === null) {
            throw ContributorNotFound::withId($id);
        }

        return Contributor::reconstitute(
            id: $id,
            name: new DisplayName($user->name),
            profile: new ContributorProfile($profile->bio, $profile->phone, $profile->photo_path),
            status: ContributorStatus::from($user->status),
            termsAcceptedAt: LocalTime::fromColumn($profile->terms_accepted_at) ?? throw new LogicException('No terms date.'),
            authorId: $profile->author_id === null ? null : AuthorId::fromInt($profile->author_id),
            approvedBy: $profile->approved_by === null ? null : UserId::fromInt($profile->approved_by),
            approvedAt: LocalTime::fromColumn($profile->approved_at),
            rejectionReason: $profile->rejection_reason,
            requestedName: $profile->requested_name === null ? null : new DisplayName($profile->requested_name),
        );
    }

    public function save(Contributor $contributor): void
    {
        $this->db->transaction(static function () use ($contributor): void {
            $user = User::query()->findOrFail($contributor->id()->value);
            $verified = $contributor->status() !== ContributorStatus::PendingEmail;

            $user->forceFill([
                'name' => $contributor->name()->value,
                'type' => 'contributor',
                'status' => $contributor->status()->value,
                'email_verified_at' => $verified ? ($user->email_verified_at ?? CarbonImmutable::now()) : null,
            ])->save();

            ContributorProfileModel::query()->updateOrCreate(['user_id' => $contributor->id()->value], [
                'author_id' => $contributor->authorId()?->value,
                'bio' => $contributor->profile()->bio,
                'phone' => $contributor->profile()->phone,
                'photo_path' => $contributor->profile()->photoPath,
                'terms_accepted_at' => LocalTime::toColumn($contributor->termsAcceptedAt()),
                'approved_by' => $contributor->approvedBy()?->value,
                'approved_at' => LocalTime::toColumn($contributor->approvedAt()),
                'rejection_reason' => $contributor->rejectionReason(),
                'requested_name' => $contributor->requestedName()?->value,
            ]);
        });
    }
}
