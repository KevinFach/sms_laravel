<?php

namespace App\Services\Channels;

use App\Models\Message;

/**
 * Contrato de un canal de envío.
 *
 * Los canales *pull* (ESP32) no hacen nada en `send()`: el dispositivo jala los
 * mensajes en `en_cola` y confirma por el plano gateway. Los canales *push*
 * (51x.dev) entregan el mensaje al proveedor y luego sincronizan su estado.
 */
interface ChannelDriver
{
    /** Empuja el mensaje al canal. Debe dejar el estado del mensaje coherente. */
    public function send(Message $message): void;

    /** Refresca el estado local a partir del estado remoto del mensaje. */
    public function syncStatus(Message $message): void;

    /** Propaga la cancelación al proveedor. `false` si ya no se pudo frenar. */
    public function cancel(Message $message): bool;
}
