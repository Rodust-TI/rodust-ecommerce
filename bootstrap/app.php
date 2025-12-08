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
    ->withMiddleware(function (Middleware $middleware): void {
        // Aplicar CORS globalmente em todas as rotas
        $middleware->use([
            \App\Http\Middleware\CorsMiddleware::class,
        ]);
        
        // Desabilitar CSRF para rotas API (stateless)
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        
        // Middleware para forçar reset de senha
        $middleware->alias([
            'require.password.reset' => \App\Http\Middleware\RequirePasswordReset::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
