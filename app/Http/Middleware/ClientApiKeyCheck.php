<?php

namespace App\Http\Middleware;

use App\Models\ClientApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica a un sistema integrador (p.ej. notifica_ya) por el header `X-API-Key` y adjunta
 * el Client dueño de la key a la request (`$request->attributes->get('client')`).
 */
class ClientApiKeyCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainKey = $request->header('X-API-Key');

        if (! $plainKey) {
            return response()->json(['error' => 'Falta la API key (header X-API-Key).'], 401);
        }

        $apiKey = ClientApiKey::findActiveByPlainKey($plainKey);

        if (! $apiKey) {
            return response()->json(['error' => 'API key inválida o revocada.'], 401);
        }

        $client = $apiKey->client;

        if (! $client->isActivo()) {
            return response()->json(['error' => 'El cliente asociado a esta API key está inactivo.'], 403);
        }

        $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();

        $request->attributes->set('client', $client);
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
