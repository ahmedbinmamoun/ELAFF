<?php

declare(strict_types=1);

use Elaf\Content\Presentation\Console\PublishScheduledArticlesCommand;
use Elaf\Publishing\Presentation\Console\ImportIssuesCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        PublishScheduledArticlesCommand::class,
        ImportIssuesCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // TLS ends at Cloudflare (tunnel or proxy): follow its X-Forwarded-Proto so
        // links and signed URLs use https. Only the scheme is trusted; the client
        // IP is not (rate limits), see Phase 7 for Cloudflare's real-IP header.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_PROTO);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
