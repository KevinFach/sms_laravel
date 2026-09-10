<?php

use App\Enums\MessageStatus;
use App\Models\Channel;
use App\Models\Message;

it('sigue entregando los mensajes en cola al gateway esp32', function () {
    $channel = Channel::factory()->esp32()->create(['clave' => 'clave-esp32']);
    $message = Message::factory()->enCola()->create([
        'channel_id' => $channel->id,
        'numero' => '+522291099841',
        'mensaje' => 'Prueba ESP32',
    ]);

    $this->get('/api/v1/gateway/pendientes?clave=clave-esp32')
        ->assertOk()
        ->assertSee("{$message->id}|+522291099841|Prueba ESP32");
});

it('no expone a un canal los mensajes de otro', function () {
    $propio = Channel::factory()->esp32()->create(['clave' => 'clave-propia']);
    $ajeno = Channel::factory()->api51x()->create();

    Message::factory()->enCola()->create(['channel_id' => $ajeno->id, 'mensaje' => 'ajeno']);

    $this->get('/api/v1/gateway/pendientes?clave=clave-propia')
        ->assertOk()
        ->assertDontSee('ajeno');
});

it('el gateway confirma el envio de su mensaje', function () {
    $channel = Channel::factory()->esp32()->create(['clave' => 'clave-esp32']);
    $message = Message::factory()->enCola()->create(['channel_id' => $channel->id]);

    $this->post("/api/v1/gateway/messages/{$message->id}/enviado?clave=clave-esp32")
        ->assertOk();

    expect($message->fresh()->status)->toBe(MessageStatus::Enviado);
});
