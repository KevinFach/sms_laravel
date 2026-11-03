<?php

namespace App\Http\Controllers\Api\Integration;

use App\Enums\MessageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\StoreIntegrationMessageRequest;
use App\Models\Client;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Envío y consulta de mensajes para integradores autenticados por API key.
 * Contrato compatible con `/api/messages` de 51x.dev.
 */
class MessageController extends Controller
{
    public function store(StoreIntegrationMessageRequest $request): JsonResponse
    {
        $client = $this->client($request);
        $data = $request->validated();
        $fechaEnvio = $data['fecha_envio'] ?? null;

        $message = Message::create([
            'mensaje' => $data['body'],
            'numero' => $data['to'],
            'client_id' => $client->id,
            'channel_id' => (int) $data['device_id'],
            'external_ref' => $data['external_ref'] ?? null,
            'fecha_envio' => $fechaEnvio,
            'hora_envio' => $fechaEnvio !== null ? ($data['hora_envio'] ?? '00:00') : null,
        ]);

        return response()->json([
            'id' => (string) $message->msg_id,
            'message_number' => (string) $message->id,
            'status' => $message->status->toIntegration(),
            'device_id' => (string) $message->channel_id,
            'scheduled_at' => $this->scheduledAt($message),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $message = $this->find($request, $id);

        if (! $message) {
            return response()->json(['error' => 'Mensaje no encontrado.'], 404);
        }

        return response()->json([
            'id' => (string) $message->msg_id,
            'message_number' => (string) $message->id,
            'status' => $message->status->toIntegration(),
            'device_id' => $message->channel_id ? (string) $message->channel_id : null,
            'error' => $message->error_message,
            'scheduled_at' => $this->scheduledAt($message),
            'sent_at' => $message->sent_at?->toIso8601String(),
        ]);
    }

    public function cancel(Request $request, string $id): JsonResponse|Response
    {
        $message = $this->find($request, $id);

        if (! $message) {
            return response()->json(['error' => 'Mensaje no encontrado.'], 404);
        }

        try {
            $message->transitionTo(MessageStatus::Cancelado);
        } catch (\DomainException) {
            return response()->json(['error' => 'No se puede cancelar un mensaje ya enviado o finalizado.'], 422);
        }

        return response()->noContent();
    }

    private function client(Request $request): Client
    {
        return $request->attributes->get('client');
    }

    private function find(Request $request, string $id): ?Message
    {
        return Message::query()
            ->where('client_id', $this->client($request)->id)
            ->where('msg_id', $id)
            ->first();
    }

    /**
     * fecha_envio/hora_envio se guardan como hora local (config sms.timezone).
     */
    private function scheduledAt(Message $message): ?string
    {
        $scheduledAt = $message->scheduledAt();

        if ($scheduledAt === null) {
            return null;
        }

        return Carbon::parse($scheduledAt->format('Y-m-d H:i:s'), config('sms.timezone'))->toIso8601String();
    }
}
