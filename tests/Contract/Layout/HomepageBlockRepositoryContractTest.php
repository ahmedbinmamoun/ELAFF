<?php

declare(strict_types=1);

use Elaf\Layout\Application\Homepage\HomepageBlock;
use Elaf\Layout\Application\Homepage\HomepageBlockData;
use Elaf\Layout\Domain\Homepage\BlockRegion;
use Elaf\Layout\Infrastructure\Persistence\EloquentHomepageBlocks;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\InMemoryHomepageBlocks;

uses(RefreshDatabase::class);

dataset('homepage block stores', [
    'in-memory' => fn (): InMemoryHomepageBlocks|EloquentHomepageBlocks => new InMemoryHomepageBlocks,
    'eloquent' => fn (): InMemoryHomepageBlocks|EloquentHomepageBlocks => new EloquentHomepageBlocks(app(ConnectionInterface::class)),
]);

it('keeps blocks in order with their options; active() is null until a block exists', function (InMemoryHomepageBlocks|EloquentHomepageBlocks $store): void {
    expect($store->active())->toBeNull();

    $a = $store->add(new HomepageBlockData('hero', BlockRegion::Top, ['secondary' => 3, 'title' => 'عنوان', 'on' => true]));
    $b = $store->add(new HomepageBlockData('most_read', BlockRegion::Aside, [], false));
    $c = $store->add(new HomepageBlockData('latest_articles', BlockRegion::Main));

    expect($store->find($a)?->data)->toEqual(new HomepageBlockData('hero', BlockRegion::Top, ['secondary' => 3, 'title' => 'عنوان', 'on' => true]))
        ->and(array_map(static fn (HomepageBlockData $d): string => $d->type, $store->active() ?? []))->toBe(['hero', 'latest_articles']);

    $store->reorder([$c, $b, $a]);
    $store->update($b, new HomepageBlockData('most_read', BlockRegion::Bottom, ['limit' => 3], true));
    $store->remove($c);

    expect(array_map(static fn (HomepageBlock $x): array => [$x->data->type, $x->data->region->value], $store->all()))
        ->toBe([['most_read', 'bottom'], ['hero', 'top']])
        ->and($store->find($c))->toBeNull();

    $store->remove($a);
    $store->remove($b);
    expect($store->active())->toBeNull();
})->with('homepage block stores');
