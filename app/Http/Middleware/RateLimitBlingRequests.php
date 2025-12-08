<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate Limiting para requisições ao Bling API
 * 
 * Limites do Bling:
 * - 3 requisições por segundo
 * - 120.000 requisições por dia
 * 
 * Este middleware garante que não ultrapassemos 3 req/s
 */
class RateLimitBlingRequests
{
    protected const MAX_REQUESTS_PER_SECOND = 3;
    protected const WINDOW_SECONDS = 1;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cacheKey = 'bling_rate_limit';
        $now = now()->timestamp;
        
        // Obter requisições dos últimos segundos
        $requests = Cache::get($cacheKey, []);
        
        // Filtrar apenas requisições do último segundo
        $recentRequests = array_filter($requests, function($timestamp) use ($now) {
            return ($now - $timestamp) < self::WINDOW_SECONDS;
        });
        
        // Se já temos 3 requisições no último segundo, aguardar
        if (count($recentRequests) >= self::MAX_REQUESTS_PER_SECOND) {
            $oldestRequest = min($recentRequests);
            $waitTime = self::WINDOW_SECONDS - ($now - $oldestRequest) + 0.1; // +0.1s de margem
            
            if ($waitTime > 0) {
                Log::debug('Bling rate limit: aguardando', [
                    'wait_seconds' => $waitTime,
                    'recent_requests' => count($recentRequests)
                ]);
                usleep((int)($waitTime * 1000000)); // Converter para microsegundos
            }
        }
        
        // Registrar esta requisição
        $requests[] = now()->timestamp;
        // Manter apenas últimas 10 requisições (otimização)
        $requests = array_slice($requests, -10);
        Cache::put($cacheKey, $requests, 5); // Cache por 5 segundos
        
        return $next($request);
    }
}
