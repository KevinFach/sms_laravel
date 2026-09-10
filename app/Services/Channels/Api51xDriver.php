<?php

namespace App\Services\Channels;

use App\Enums\MessageStatus;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Canal 51x.dev: servicio externo que manda los SMS desde un celular Android.
 *
 * Se le empuja el mensaje con `POST /api/messages` (autenticado con `X-API-Key`),
 * que responde 202 con un id remoto; después se consulta ese id para saber si el
 * SMS salió. La programación NO se delega: el dispatcher retiene el mensaje hasta
 * que vence y recién entonces lo empuja.
 *
 * Spec: https://51x.dev/docs/openapi.yaml
 */
class Api51xDriver implements ChannelDriver
{
    public function __construct(private readonly Channel $channel) {}

    public function send(Message $message): void
    {
        try {
            $response = $this->client()->post('/api/messages', array_filter([
                'to' => $this->normalizeRecipient($message->numero),
                'channel' => 'sms',
                'body' => $message->mensaje,
                'device_id' => $this->channel->configValue('device_id'),
            ], fn ($value) => $value !== null && $value !== ''));
        } catch (\Throwable $e) {
            $this->fail($message, $e->getMessage());

            return;
        }

        if ($response->failed()) {
            $this->fail($message, $this->errorFrom($response));

            return;
        }

        $externalId = $response->json('id');

        if (blank($externalId)) {
            $this->fail($message, '51x.dev aceptó el mensaje pero no devolvió un id.');

            return;
        }

        $message->external_id = $externalId;
        $message->save();
    }

    public function syncStatus(Message $message): void
    {
        if (blank($message->external_id)) {
            return;
        }

        try {
            $response = $this->client()->get("/api/messages/{$message->external_id}");
        } catch (\Throwable $e) {
            // Fallo transitorio de red: se reintenta en la próxima corrida.
            Log::warning('51x.dev: no se pudo consultar el estado del mensaje.', [
                'message_id' => $message->id,
                'external_id' => $message->external_id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($response->notFound()) {
            $this->fail($message, 'El mensaje ya no existe en 51x.dev.');

            return;
        }

        if ($response->failed()) {
            Log::warning('51x.dev: respuesta inesperada al consultar el estado.', [
                'message_id' => $message->id,
                'status' => $response->status(),
            ]);

            return;
        }

        $target = match ($response->json('status')) {
            'sent' => MessageStatus::Enviado,
            'failed' => MessageStatus::Error,
            'cancelled' => MessageStatus::Cancelado,
            default => null, // scheduled / pending: sigue en cola
        };

        if ($target === null) {
            return;
        }

        if ($target === MessageStatus::Error) {
            $message->error_message = $response->json('error') ?? 'Envío fallido reportado por 51x.dev.';
        }

        $this->transition($message, $target);
    }

    public function cancel(Message $message): bool
    {
        if (blank($message->external_id)) {
            return true;
        }

        try {
            $response = $this->client()->post("/api/messages/{$message->external_id}/cancel");
        } catch (\Throwable $e) {
            Log::warning('51x.dev: no se pudo cancelar el mensaje.', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return $response->successful();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->channel->configValue('base_url') ?: config('services.51x.base_url'), '/'))
            ->withHeaders(['X-API-Key' => (string) $this->channel->configValue('api_key')])
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 300, throw: false);
    }

    /**
     * 51x.dev espera números en E.164. Si el número no trae `+`, se le antepone
     * el prefijo de país configurado en el canal.
     */
    private function normalizeRecipient(string $numero): string
    {
        $numero = preg_replace('/[^0-9+]/', '', trim($numero));

        if (str_starts_with($numero, '+')) {
            return $numero;
        }

        $prefijo = rtrim((string) $this->channel->configValue('pais', '+52'), ' ');

        return $prefijo.ltrim($numero, '0');
    }

    private function errorFrom(Response $response): string
    {
        return $response->json('error')
            ?? "51x.dev respondió {$response->status()}.";
    }

    private function fail(Message $message, string $error): void
    {
        $message->error_message = $error;
        $this->transition($message, MessageStatus::Error);
    }

    private function transition(Message $message, MessageStatus $to): void
    {
        try {
            $message->transitionTo($to);
        } catch (\DomainException $e) {
            Log::warning('51x.dev: transición de estado rechazada.', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
