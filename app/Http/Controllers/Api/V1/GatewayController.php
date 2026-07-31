<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MessageStatus;
use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Http\Request;

/**
 * API del plano gateway (ESP32). Autenticada por `clave` de canal (middleware channel.token).
 * Cada canal solo ve y confirma sus propios mensajes.
 */
class GatewayController extends Controller
{
    private function channel(Request $request): Channel
    {
        return $request->attributes->get('channel');
    }

    /**
     * Mensajes en cola para este canal, en formato de texto plano pipe-delimited
     * (`id|numero|mensaje`), compatible con el firmware ESP32 actual.
     */
    public function pendientes(Request $request)
    {
        $channel = $this->channel($request);

        $mensajes = Message::query()
            ->where('status', MessageStatus::EnCola->value)
            ->where('channel_id', $channel->id)
            ->get(['id', 'numero', 'mensaje']);

        $salida = '';
        foreach ($mensajes as $m) {
            $salida .= "{$m->id}|{$m->numero}|{$m->mensaje}\n";
        }

        return response($salida, 200)->header('Content-Type', 'text/plain');
    }

    /** El canal confirma que el SMS salió. */
    public function enviado(Request $request, int $id)
    {
        $mensaje = $this->findForChannel($request, $id);

        if (! $mensaje) {
            return response()->json(['error' => 'Mensaje no encontrado para este canal.'], 404);
        }

        try {
            $mensaje->transitionTo(MessageStatus::Enviado);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Mensaje {$mensaje->msg_id} marcado como enviado.",
            'status' => $mensaje->status->value,
        ]);
    }

    /** El canal reporta una falla de envío. */
    public function error(Request $request, int $id)
    {
        $mensaje = $this->findForChannel($request, $id);

        if (! $mensaje) {
            return response()->json(['error' => 'Mensaje no encontrado para este canal.'], 404);
        }

        $mensaje->error_message = $request->input('error')
            ?? $request->input('mensaje')
            ?? 'Error reportado por el canal';

        try {
            $mensaje->transitionTo(MessageStatus::Error);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Mensaje {$mensaje->msg_id} marcado con error.",
            'status' => $mensaje->status->value,
        ]);
    }

    private function findForChannel(Request $request, int $id): ?Message
    {
        $channel = $this->channel($request);

        return Message::query()
            ->where('id', $id)
            ->where('channel_id', $channel->id)
            ->first();
    }
}
