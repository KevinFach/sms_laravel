<?php

namespace Database\Seeders;

use App\Models\Channel;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    /**
     * Crea el canal predeterminado. Reutiliza el MESSAGE_API_TOKEN existente como
     * `clave` para que el gateway ESP32 ya desplegado siga autenticando sin cambios.
     */
    public function run(): void
    {
        $clave = env('MESSAGE_API_TOKEN') ?: 'canal-principal-'.str()->random(16);

        Channel::updateOrCreate(
            ['is_default' => true],
            [
                'clave' => $clave,
                'tipo' => 'esp32',
                'nombre' => 'Canal Principal',
                'status' => 'activo',
            ]
        );
    }
}
