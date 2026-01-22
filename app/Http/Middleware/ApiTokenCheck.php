<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Token desde Header
        $tokenHeader = $request->header('X-API-TOKEN');

        // 2. Token desde query string: ?token=XXXXX o ?X-API-TOKEN=XXXXX
        $tokenQuery = $request->query('token') ?? $request->query('X-API-TOKEN');

        // 3. Elegir el token válido (header > query)
        $token = $tokenHeader ?: $tokenQuery;

        // 4. Token esperado desde .env
        $expectedToken = env('MESSAGE_API_TOKEN');

        // 5. Validación
        if (!$token || $token !== $expectedToken) {
            return response()->json([
                'error' => 'Acceso no autorizado. Token API inválido o faltante.'
            ], 401);
        }

        return $next($request);
    }
}
