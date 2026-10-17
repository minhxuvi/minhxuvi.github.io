<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// Runtime files (sessions, tokens, private cache) are owner-only.
umask(0077);

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustHosts(at: fn () => [parse_url(config('app.url'), PHP_URL_HOST)], subdomains: false);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([\App\Exceptions\GitHubFailure::class, \Illuminate\Http\Client\ConnectionException::class]);
        $exceptions->render(function (\App\Exceptions\GitHubFailure $e, Request $request) {
            return $request->expectsJson()
                ? response()->json(['message' => $e->getMessage()], $e->httpStatus)
                : response()->view('errors.github', ['title' => 'Chưa kết nối được GitHub', 'message' => $e->getMessage()], $e->httpStatus);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
