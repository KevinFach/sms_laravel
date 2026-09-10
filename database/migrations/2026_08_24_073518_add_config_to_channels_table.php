<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            // `text` y no `json`: el cast `encrypted:array` guarda una cadena cifrada
            // que una columna JSON de MySQL rechazaría por no ser JSON válido.
            $table->text('config')->nullable()->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('config');
        });
    }
};
