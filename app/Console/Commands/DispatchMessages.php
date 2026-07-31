<?php

namespace App\Console\Commands;

use App\Enums\MessageStatus;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Console\Command;

/**
 * Dispatcher de la cola: toma los mensajes vencidos (por_enviar / programado cuya hora
 * ya llegó), les asigna el canal del cliente (o el predeterminado) y los pasa a `en_cola`.
 * Agendado cada minuto en routes/console.php.
 */
class DispatchMessages extends Command
{
    protected $signature = 'messages:dispatch';

    protected $description = 'Encola los mensajes vencidos asignándoles su canal de envío';

    public function handle(): int
    {
        $default = Channel::default();
        $encolados = 0;
        $errores = 0;

        Message::due()->chunkById(200, function ($mensajes) use ($default, &$encolados, &$errores) {
            foreach ($mensajes as $mensaje) {
                $channel = $mensaje->channel
                    ?? $mensaje->client?->effectiveChannel()
                    ?? $default;

                if (! $channel || ! $channel->isActivo()) {
                    $mensaje->error_message = 'No hay canal activo disponible (ni asignado ni predeterminado).';
                    $mensaje->transitionTo(MessageStatus::Error);
                    $errores++;

                    continue;
                }

                $mensaje->channel_id = $channel->id;
                $mensaje->transitionTo(MessageStatus::EnCola);
                $encolados++;
            }
        });

        $this->info("Mensajes encolados: {$encolados}. Con error de canal: {$errores}.");

        return self::SUCCESS;
    }
}
