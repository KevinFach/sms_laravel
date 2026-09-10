<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Services\Channels\ChannelDriverManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Confirma contra el proveedor remoto los mensajes ya despachados a un canal push
 * (51x.dev responde 202 "pending"; el SMS sale después). Pasa el mensaje a
 * `enviado`, `error` o `cancelado` según el estado remoto.
 * Agendado cada minuto en routes/console.php.
 */
class SyncRemoteMessages extends Command
{
    protected $signature = 'messages:sync-remote';

    protected $description = 'Sincroniza el estado de los mensajes despachados a canales remotos';

    public function handle(ChannelDriverManager $drivers): int
    {
        $revisados = 0;

        Message::query()
            ->awaitingRemoteConfirmation()
            ->with('channel')
            ->chunkById(200, function ($mensajes) use ($drivers, &$revisados) {
                foreach ($mensajes as $mensaje) {
                    try {
                        $drivers->forMessage($mensaje)->syncStatus($mensaje);
                    } catch (\Throwable $e) {
                        Log::error('Fallo al sincronizar el estado remoto del mensaje.', [
                            'message_id' => $mensaje->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $revisados++;
                }
            });

        $this->info("Mensajes revisados: {$revisados}.");

        return self::SUCCESS;
    }
}
