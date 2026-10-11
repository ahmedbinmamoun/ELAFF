<?php

declare(strict_types=1);

namespace Tests\Support\Doubles;

use Elaf\Shared\Domain\Event\RecordsEvents;

final class SampleAggregate
{
    use RecordsEvents;

    public function doSomething(string $name): void
    {
        $this->recordThat(new SampleEvent($name));
    }
}
