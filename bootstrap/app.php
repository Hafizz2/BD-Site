<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Telegram posts updates without a CSRF token; it is protected by a secret header instead.
        $middleware->validateCsrfTokens(except: ['telegram/webhook']);
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
        // Trust the hosting proxy / Cloudflare so request()->ip() and HTTPS URLs are correct.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
