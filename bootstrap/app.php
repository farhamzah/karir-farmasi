<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireCareerCapability;
use App\Http\Middleware\RequireCompanyUser;
use App\Http\Middleware\RequireCorePrincipal;
use App\Http\Middleware\RequireVerifiedCompany;
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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', HandleInertiaRequests::class);
        $middleware->alias([
            'core.principal' => RequireCorePrincipal::class,
            'career.can' => RequireCareerCapability::class,
            'company.auth' => RequireCompanyUser::class,
            'company.verified' => RequireVerifiedCompany::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
