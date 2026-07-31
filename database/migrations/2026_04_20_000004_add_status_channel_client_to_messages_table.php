<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('status')->default('por_enviar')->after('estatus');
            $table->foreignId('client_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->foreignId('channel_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            $table->text('error_message')->nullable()->after('channel_id');
            $table->timestamp('sent_at')->nullable()->after('error_message');
        });

        // Migración de datos desde el boolean `estatus` legacy hacia la máquina de estados.
        // Se hace la comparación de fecha/hora en PHP para ser portable entre motores.
        DB::table('messages')->where('estatus', 1)->update([
            'status' => 'enviado',
            'sent_at' => DB::raw('updated_at'),
        ]);

        DB::table('messages')->where('estatus', 0)
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $status = 'por_enviar';

                    if (! empty($row->fecha_envio)) {
                        $scheduled = Carbon::parse($row->fecha_envio.' '.($row->hora_envio ?? '00:00:00'));
                        if ($scheduled->isFuture()) {
                            $status = 'programado';
                        }
                    }

                    DB::table('messages')->where('id', $row->id)->update(['status' => $status]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['channel_id']);
            $table->dropColumn(['status', 'client_id', 'channel_id', 'error_message', 'sent_at']);
        });
    }
};
