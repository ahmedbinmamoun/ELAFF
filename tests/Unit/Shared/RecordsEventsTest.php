<?php

declare(strict_types=1);

use Tests\Support\Doubles\SampleAggregate;
use Tests\Support\Doubles\SampleEvent;

it('releases recorded events once, in order', function (): void {
    $aggregate = new SampleAggregate;
    $aggregate->doSomething('first');
    $aggregate->doSomething('second');

    $events = $aggregate->releaseEvents();

    expect($events)->toHaveCount(2)
        ->and($events[0])->toBeInstanceOf(SampleEvent::class)
        ->and(array_map(static fn (SampleEvent $e): string => $e->name, $events))->toBe(['first', 'second'])
        ->and($aggregate->releaseEvents())->toBe([]);
});
