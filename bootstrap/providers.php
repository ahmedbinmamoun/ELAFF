<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Elaf\Content\Infrastructure\ContentServiceProvider;
use Elaf\Shared\Infrastructure\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    ContentServiceProvider::class,
];
