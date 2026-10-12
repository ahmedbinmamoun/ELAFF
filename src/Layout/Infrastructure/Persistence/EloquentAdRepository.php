<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure\Persistence;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Elaf\Content\Infrastructure\Persistence\Eloquent\LocalTime;
use Elaf\Layout\Domain\Ad\Ad;
use Elaf\Layout\Domain\Ad\AdCreative;
use Elaf\Layout\Domain\Ad\AdId;
use Elaf\Layout\Domain\Ad\AdRepository;
use Elaf\Layout\Domain\Ad\AdSlot;
use Elaf\Layout\Domain\Ad\DateRange;
use Elaf\Layout\Domain\Ad\Exception\AdNotFound;
use Illuminate\Database\ConnectionInterface;

final readonly class EloquentAdRepository implements AdRepository
{
    public function __construct(private ConnectionInterface $db) {}

    public function nextId(): AdId
    {
        return AdId::fromInt((int) $this->db->table('ad_id_sequence')->insertGetId([]));
    }

    public function get(AdId $id): Ad
    {
        $m = AdModel::query()->find($id->value) ?? throw AdNotFound::withId($id);
        $creative = $m->image_path !== null ? AdCreative::image($m->image_path) : AdCreative::html((string) $m->html);

        return Ad::reconstitute(
            $id, $m->title, AdSlot::from($m->slot), $creative, $m->target_url,
            new DateRange(LocalTime::fromColumn($m->starts_at), LocalTime::fromColumn($m->ends_at)),
            $m->weight, $m->is_active,
        );
    }

    public function save(Ad $ad): void
    {
        $model = AdModel::query()->find($ad->id()->value) ?? new AdModel(['id' => $ad->id()->value]);
        $model->fill([
            'title' => $ad->title(),
            'slot' => $ad->slot()->value,
            'image_path' => $ad->creative()->imagePath,
            'html' => $ad->creative()->html,
            'target_url' => $ad->targetUrl(),
            'starts_at' => self::column($ad->schedule()->startsAt),
            'ends_at' => self::column($ad->schedule()->endsAt),
            'weight' => $ad->weight(),
            'is_active' => $ad->isActive(),
        ])->save();
    }

    public function delete(AdId $id): void
    {
        AdModel::query()->whereKey($id->value)->delete();
    }

    private static function column(?DateTimeImmutable $moment): ?CarbonImmutable
    {
        $local = LocalTime::toColumn($moment);

        return $local === null ? null : CarbonImmutable::instance($local);
    }
}
