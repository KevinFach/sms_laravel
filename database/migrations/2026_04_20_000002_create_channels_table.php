<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();      // token del gateway
            $table->string('tipo')->default('esp32');     // esp32 | otro
            $table->string('nombre');
            $table->string('telefono')->nullable();
            $table->string('status')->default('activo');   // activo | inactivo
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};
