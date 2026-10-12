<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure\Persistence;

use Elaf\Content\Infrastructure\Persistence\Eloquent\LocalTime;
use Elaf\Layout\Application\Ad\AdClicks;
use Elaf\Layout\Application\Ad\Query\AdCandidate;
use Elaf\Layout\Application\Ad\Query\AdQueries;
use Elaf\Layout\Domain\Ad\AdId;

/**
 * Bound per request (scoped): the active ads are read once however many
 * slots a page has.
 */
final class EloquentAdQueries implements AdClicks, AdQueries
{
    /** @var list<AdCandidate>|null */
    private ?array $active = null;

    public function active(): array
    {
        return $this->active ??= array_values(AdModel::query()->where('is_active', true)->orderBy('id')->get()
            ->map(static fn (AdModel $m): AdCandidate => new AdCandidate(
                $m->id, $m->slot, $m->title, $m->image_path, $m->html, $m->target_url, $m->weight,
                LocalTime::fromColumn($m->starts_at), LocalTime::fromColumn($m->ends_at),
            ))
            ->all());
    }

    public function target(int $adId): ?string
    {
        $url = AdModel::query()->whereKey($adId)->value('target_url');

        return is_string($url) && $url !== '' ? $url : null;
    }

    /**
     * One atomic UPDATE; never touches updated_at.
     */
    public function record(AdId $id): void
    {
        AdModel::query()->whereKey($id->value)->toBase()->increment('clicks_count');
    }
}
