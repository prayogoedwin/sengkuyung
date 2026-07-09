<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'auth-api' => \App\Http\Middleware\SanctumRememberToken::class,
            'auth-api-jr' => \App\Http\Middleware\SanctumRememberToken::class,
            'deny.petugas.web' => \App\Http\Middleware\DenyPetugasWeb::class,
            'field-officer.api' => \App\Http\Middleware\EnsureFieldOfficerApi::class,
            'petugas.api' => \App\Http\Middleware\EnsurePetugasApi::class,
            'petugas-d2d.api' => \App\Http\Middleware\EnsurePetugasD2dApi::class,
            'jasa-raharja.api' => \App\Http\Middleware\EnsureJasaRaharjaApi::class,
            'maintenance.check' => \App\Http\Middleware\EnsureNotInMaintenance::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureNotInMaintenance::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\EnsureNotInMaintenance::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
