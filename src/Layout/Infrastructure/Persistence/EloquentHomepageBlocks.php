<?php

declare(strict_types=1);

namespace Elaf\Layout\Infrastructure\Persistence;

use Elaf\Layout\Application\Homepage\HomepageBlock;
use Elaf\Layout\Application\Homepage\HomepageBlockData;
use Elaf\Layout\Application\Homepage\HomepageBlockQueries;
use Elaf\Layout\Application\Homepage\HomepageBlockRepository;
use Elaf\Layout\Domain\Homepage\BlockRegion;
use Illuminate\Database\ConnectionInterface;

final readonly class EloquentHomepageBlocks implements HomepageBlockQueries, HomepageBlockRepository
{
    public function __construct(private ConnectionInterface $db) {}

    public function all(): array
    {
        return array_values(HomepageBlockModel::query()->orderBy('position')->orderBy('id')->get()
            ->map(fn (HomepageBlockModel $m): HomepageBlock => new HomepageBlock($m->id, $this->data($m)))
            ->all());
    }

    public function active(): ?array
    {
        $rows = HomepageBlockModel::query()->orderBy('position')->orderBy('id')->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return array_values($rows->filter(static fn (HomepageBlockModel $m): bool => $m->is_active)
            ->map(fn (HomepageBlockModel $m): HomepageBlockData => $this->data($m))
            ->all());
    }

    public function find(int $id): ?HomepageBlock
    {
        $model = HomepageBlockModel::query()->find($id);

        return $model === null ? null : new HomepageBlock($model->id, $this->data($model));
    }

    public function add(HomepageBlockData $data): int
    {
        $position = HomepageBlockModel::query()->max('position');

        return HomepageBlockModel::query()->create([
            ...$this->columns($data),
            'position' => is_numeric($position) ? (int) $position + 1 : 0,
        ])->id;
    }

    public function update(int $id, HomepageBlockData $data): void
    {
        HomepageBlockModel::query()->findOrFail($id)->fill($this->columns($data))->save();
    }

    public function reorder(array $ids): void
    {
        $this->db->transaction(static function () use ($ids): void {
            foreach ($ids as $position => $id) {
                HomepageBlockModel::query()->whereKey($id)->update(['position' => $position]);
            }
        });
    }

    public function remove(int $id): void
    {
        HomepageBlockModel::query()->whereKey($id)->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function columns(HomepageBlockData $data): array
    {
        return ['type' => $data->type, 'region' => $data->region->value, 'config' => $data->options, 'is_active' => $data->isActive];
    }

    private function data(HomepageBlockModel $m): HomepageBlockData
    {
        $options = [];

        foreach ($m->config ?? [] as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $options[$key] = is_float($value) ? (int) $value : $value;
            }
        }

        return new HomepageBlockData($m->type, BlockRegion::tryFrom($m->region) ?? BlockRegion::Main, $options, $m->is_active);
    }
}
