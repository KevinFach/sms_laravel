<?php

namespace App\Console\Commands;

use App\Enums\MessageStatus;
use App\Models\Channel;
use App\Models\Message;
use App\Services\Channels\ChannelDriverManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Dispatcher de la cola: toma los mensajes vencidos (por_enviar / programado cuya hora
 * ya llegó), les asigna el canal del cliente (o el predeterminado) y los pasa a `en_cola`.
 * Para los canales push (51x.dev) además entrega el mensaje al proveedor.
 * Agendado cada minuto en routes/console.php.
 */
class DispatchMessages extends Command
{
    protected $signature = 'messages:dispatch';

    protected $description = 'Encola los mensajes vencidos asignándoles su canal de envío';

    public function handle(ChannelDriverManager $drivers): int
    {
        $default = Channel::default();
        $encolados = 0;
        $errores = 0;

        Message::due()->chunkById(200, function ($mensajes) use ($drivers, $default, &$encolados, &$errores) {
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
                $mensaje->setRelation('channel', $channel);
                $mensaje->transitionTo(MessageStatus::EnCola);

                // Un canal caído no debe tumbar el lote completo.
                try {
                    $drivers->driver($channel)->send($mensaje);
                } catch (\Throwable $e) {
                    Log::error('Fallo al entregar el mensaje al canal.', [
                        'message_id' => $mensaje->id,
                        'channel_id' => $channel->id,
                        'error' => $e->getMessage(),
                    ]);

                    $mensaje->error_message = $e->getMessage();
                    $mensaje->transitionTo(MessageStatus::Error);
                }

                if ($mensaje->status === MessageStatus::Error) {
                    $errores++;

                    continue;
                }

                $encolados++;
            }
        });

        $this->info("Mensajes encolados: {$encolados}. Con error de canal: {$errores}.");

        return self::SUCCESS;
    }
}
