<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordReset
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_reset_password) {
            // Permitir acesso apenas às rotas de reset de senha e logout
            if (!$request->routeIs('password.*') && !$request->routeIs('logout') && !$request->routeIs('google.logout')) {
                // Se for uma requisição AJAX/API, retornar JSON
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'password_reset_required',
                        'message' => 'Você precisa criar uma senha antes de continuar.',
                        'redirect_url' => route('password.force-reset'),
                    ], 403);
                }
                
                // Se for uma requisição web, redirecionar
                return redirect()->route('password.force-reset')
                    ->with('warning', 'Por favor, crie uma senha para sua conta antes de continuar.');
            }
        }

        return $next($request);
    }
}
