<?php

namespace App\Services\Channels;

use App\Models\Message;

/**
 * Canales que jalan sus propios mensajes (ESP32 y "Otro").
 *
 * No hay nada que empujar: el mensaje queda en `en_cola` y el dispositivo lo
 * recoge en `GET /api/v1/gateway/pendientes`, confirmando por `/enviado` o `/error`.
 */
class PullDriver implements ChannelDriver
{
    public function send(Message $message): void
    {
        // El dispositivo recoge el mensaje por su cuenta.
    }

    public function syncStatus(Message $message): void
    {
        // El estado lo reporta el propio dispositivo contra el plano gateway.
    }

    public function cancel(Message $message): bool
    {
        // Basta con sacarlo de `en_cola`: deja de aparecer en /pendientes.
        return true;
    }
}
