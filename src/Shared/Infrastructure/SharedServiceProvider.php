<?php

declare(strict_types=1);

namespace Elaf\Shared\Infrastructure;

use DateTimeZone;
use Elaf\Shared\Application\Authorization\Authorizer;
use Elaf\Shared\Application\Event\EventDispatcher;
use Elaf\Shared\Application\Transaction\Transaction;
use Elaf\Shared\Domain\Time\Clock;
use Elaf\Shared\Infrastructure\Authorization\GateAuthorizer;
use Elaf\Shared\Infrastructure\Event\LaravelEventDispatcher;
use Elaf\Shared\Infrastructure\Time\SystemClock;
use Elaf\Shared\Infrastructure\Transaction\DbTransaction;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class SharedServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        Transaction::class => DbTransaction::class,
        Authorizer::class => GateAuthorizer::class,
    ];

    public function register(): void
    {
        $this->app->singleton(Clock::class, static function (Application $app): Clock {
            $timezone = $app->make(Repository::class)->get('app.timezone');

            return new SystemClock(new DateTimeZone(is_string($timezone) ? $timezone : 'UTC'));
        });

        $this->app->singleton(EventDispatcher::class, static function (Application $app): EventDispatcher {
            return new LaravelEventDispatcher($app->make(Dispatcher::class), $app->make('db.transactions'));
        });
    }
}
