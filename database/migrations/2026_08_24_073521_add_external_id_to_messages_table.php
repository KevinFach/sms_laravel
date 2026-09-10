<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Identificador del mensaje en el proveedor remoto (p.ej. 51x.dev),
            // usado para consultar su estado y cancelarlo.
            $table->string('external_id', 100)->nullable()->after('channel_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['external_id']);
            $table->dropColumn('external_id');
        });
    }
};
