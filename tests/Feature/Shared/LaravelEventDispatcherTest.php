<?php

declare(strict_types=1);

use Elaf\Shared\Application\Event\EventDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\Doubles\SampleEvent;

it('delivers events immediately outside a transaction', function (): void {
    Event::fake([SampleEvent::class]);

    app(EventDispatcher::class)->dispatch(new SampleEvent('a'));

    Event::assertDispatched(SampleEvent::class);
});

it('delivers events only after the transaction commits', function (): void {
    Event::fake([SampleEvent::class]);

    DB::transaction(function (): void {
        app(EventDispatcher::class)->dispatch(new SampleEvent('a'));
        Event::assertNotDispatched(SampleEvent::class);
    });

    Event::assertDispatched(SampleEvent::class);
});

it('drops events when the transaction rolls back', function (): void {
    Event::fake([SampleEvent::class]);

    try {
        DB::transaction(function (): never {
            app(EventDispatcher::class)->dispatch(new SampleEvent('a'));
            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    Event::assertNotDispatched(SampleEvent::class);
});
