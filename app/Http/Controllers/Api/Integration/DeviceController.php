<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Consulta de dispositivos (ESP32) disponibles para el cliente de la API key.
 * Contrato compatible con `GET /api/devices` de 51x.dev.
 */
class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->attributes->get('client');

        return response()->json(
            $client->allowedChannels()->map(fn (Channel $channel): array => self::present($channel))->values()
        );
    }

    /**
     * @return array{id: string, name: string, active: bool, connected: bool, created_at: string|null, last_seen_at: string|null}
     */
    public static function present(Channel $channel): array
    {
        return [
            'id' => (string) $channel->id,
            'name' => $channel->nombre,
            'active' => $channel->isActivo(),
            'connected' => $channel->isConnected(),
            'created_at' => $channel->created_at?->toIso8601String(),
            'last_seen_at' => $channel->last_seen_at?->toIso8601String(),
        ];
    }
}
