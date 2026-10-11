<?php

declare(strict_types=1);

namespace Elaf\Content\Infrastructure\Persistence\Eloquent;

use Elaf\Content\Application\Author\Author;
use Elaf\Content\Application\Author\AuthorData;
use Elaf\Content\Application\Author\AuthorRepository;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Domain\Text\Slug;

final class EloquentAuthorRepository implements AuthorRepository
{
    public function create(AuthorData $data): AuthorId
    {
        return AuthorId::fromInt(AuthorModel::query()->create($this->columns($data))->id);
    }

    public function update(AuthorId $id, AuthorData $data): void
    {
        AuthorModel::query()->whereKey($id->value)->firstOrFail()->update($this->columns($data));
    }

    public function find(AuthorId $id): ?Author
    {
        return $this->toDto(AuthorModel::query()->find($id->value));
    }

    public function findByUser(UserId $userId): ?Author
    {
        return $this->toDto(AuthorModel::query()->where('user_id', $userId->value)->first());
    }

    /**
     * Soft-deleted authors keep their slug reserved: their URLs may be cached.
     */
    public function slugExists(Slug $slug, ?AuthorId $except = null): bool
    {
        return AuthorModel::withTrashed()
            ->where('slug', $slug->value)
            ->when($except !== null, static fn ($q) => $q->whereKeyNot($except?->value))
            ->exists();
    }

    private function toDto(?AuthorModel $model): ?Author
    {
        return $model === null ? null : new Author(AuthorId::fromInt($model->id), new AuthorData(
            name: $model->name,
            slug: Slug::fromString($model->slug),
            title: $model->title,
            bio: $model->bio,
            photoPath: $model->photo_path,
            isColumnist: $model->is_columnist,
            userId: $model->user_id === null ? null : UserId::fromInt($model->user_id),
            socialLinks: $model->social_links ?? [],
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function columns(AuthorData $data): array
    {
        return [
            'name' => $data->name,
            'slug' => $data->slug->value,
            'title' => $data->title,
            'bio' => $data->bio,
            'photo_path' => $data->photoPath,
            'is_columnist' => $data->isColumnist,
            'user_id' => $data->userId?->value,
            'social_links' => $data->socialLinks === [] ? null : $data->socialLinks,
        ];
    }
}
