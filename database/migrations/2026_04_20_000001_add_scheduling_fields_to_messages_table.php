<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('nombre', 255)->nullable()->after('msg_id');
            $table->date('fecha_evento')->nullable()->after('nombre');
            $table->time('hora_evento')->nullable()->after('fecha_evento');
            $table->date('fecha_envio')->nullable()->after('hora_evento');
            $table->time('hora_envio')->nullable()->after('fecha_envio');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'fecha_evento', 'hora_evento', 'fecha_envio', 'hora_envio']);
        });
    }
};
