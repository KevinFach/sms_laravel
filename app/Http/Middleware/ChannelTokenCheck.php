<?php

namespace App\Http\Middleware;

use App\Models\Channel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentica a un gateway por su `clave` de canal y adjunta el Channel resuelto
 * a la request (`$request->attributes->get('channel')`).
 *
 * Acepta la clave por header `X-CHANNEL-KEY` / `X-API-TOKEN` o query `?clave=` / `?token=`,
 * de modo que un gateway ESP32 existente (que envía X-API-TOKEN) siga funcionando si su
 * clave coincide con la de un canal activo.
 */
class ChannelTokenCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        $clave = $request->header('X-CHANNEL-KEY')
            ?? $request->header('X-API-TOKEN')
            ?? $request->query('clave')
            ?? $request->query('token');

        if (! $clave) {
            return response()->json([
                'error' => 'Falta la clave del canal.',
            ], 401);
        }

        $channel = Channel::query()
            ->where('clave', $clave)
            ->where('status', 'activo')
            ->first();

        if (! $channel) {
            return response()->json([
                'error' => 'Canal no autorizado o inactivo.',
            ], 401);
        }

        $request->attributes->set('channel', $channel);

        return $next($request);
    }
}
