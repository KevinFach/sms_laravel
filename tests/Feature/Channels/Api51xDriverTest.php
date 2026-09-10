<?php

use App\Enums\MessageStatus;
use App\Models\Channel;
use App\Models\Message;
use App\Services\Channels\Api51xDriver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function api51xChannel(array $config = []): Channel
{
    return Channel::factory()->api51x($config)->create();
}

it('empuja el mensaje a 51x y guarda el id remoto', function () {
    Http::fake([
        'https://51x.test/api/messages' => Http::response([
            'id' => 'msg-remoto-1',
            'message_number' => '04217',
            'status' => 'pending',
        ], 202),
    ]);

    $channel = api51xChannel();
    // El dispatcher ya dejó el mensaje en `en_cola` antes de invocar al driver.
    $message = Message::factory()->enCola()->create([
        'channel_id' => $channel->id,
        'numero' => '+522291099841',
        'mensaje' => 'Hola desde la plataforma',
    ]);

    (new Api51xDriver($channel))->send($message);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://51x.test/api/messages'
        && $request->method() === 'POST'
        && $request->header('X-API-Key') === ['test-api-key']
        && $request['to'] === '+522291099841'
        && $request['channel'] === 'sms'
        && $request['body'] === 'Hola desde la plataforma'
        && $request['device_id'] === 'dev-1');

    expect($message->fresh()->external_id)->toBe('msg-remoto-1')
        ->and($message->fresh()->status)->toBe(MessageStatus::EnCola);
});

it('antepone el prefijo de pais a los numeros sin +', function () {
    Http::fake(['*' => Http::response(['id' => 'msg-1'], 202)]);

    $channel = api51xChannel(['pais' => '+52']);
    $message = Message::factory()->create([
        'channel_id' => $channel->id,
        'numero' => '2291099841',
    ]);

    (new Api51xDriver($channel))->send($message);

    Http::assertSent(fn (Request $request) => $request['to'] === '+522291099841');
});

it('omite device_id cuando el canal no lo tiene configurado', function () {
    Http::fake(['*' => Http::response(['id' => 'msg-1'], 202)]);

    $channel = api51xChannel(['device_id' => null]);
    $message = Message::factory()->create(['channel_id' => $channel->id]);

    (new Api51xDriver($channel))->send($message);

    Http::assertSent(fn (Request $request) => ! array_key_exists('device_id', $request->data()));
});

it('marca el mensaje con error cuando 51x rechaza el envio', function () {
    Http::fake(['*' => Http::response(['error' => 'device_id requerido'], 400)]);

    $channel = api51xChannel();
    $message = Message::factory()->create(['channel_id' => $channel->id]);

    (new Api51xDriver($channel))->send($message);

    $message->refresh();

    expect($message->status)->toBe(MessageStatus::Error)
        ->and($message->error_message)->toBe('device_id requerido')
        ->and($message->external_id)->toBeNull();
});

it('marca error cuando 51x acepta pero no devuelve id', function () {
    Http::fake(['*' => Http::response(['status' => 'pending'], 202)]);

    $channel = api51xChannel();
    $message = Message::factory()->create(['channel_id' => $channel->id]);

    (new Api51xDriver($channel))->send($message);

    expect($message->fresh()->status)->toBe(MessageStatus::Error);
});

it('sincroniza el estado remoto', function (string $remoto, MessageStatus $esperado) {
    Http::fake(['*' => Http::response(['status' => $remoto, 'error' => 'sin senal'], 200)]);

    $channel = api51xChannel();
    $message = Message::factory()->enCola('msg-remoto-1')->create(['channel_id' => $channel->id]);

    (new Api51xDriver($channel))->syncStatus($message);

    expect($message->fresh()->status)->toBe($esperado);
})->with([
    'enviado' => ['sent', MessageStatus::Enviado],
    'fallido' => ['failed', MessageStatus::Error],
    'cancelado' => ['cancelled', MessageStatus::Cancelado],
    'sigue pendiente' => ['pending', MessageStatus::EnCola],
    'sigue programado' => ['scheduled', MessageStatus::EnCola],
]);

it('guarda el error remoto al sincronizar un envio fallido', function () {
    Http::fake(['*' => Http::response(['status' => 'failed', 'error' => 'sin senal'], 200)]);

    $channel = api51xChannel();
    $message = Message::factory()->enCola('msg-remoto-1')->create(['channel_id' => $channel->id]);

    (new Api51xDriver($channel))->syncStatus($message);

    expect($message->fresh()->error_message)->toBe('sin senal');
});

it('marca error si el mensaje ya no existe en 51x', function () {
    Http::fake(['*' => Http::response(['error' => 'not found'], 404)]);

    $channel = api51xChannel();
    $message = Message::factory()->enCola('msg-remoto-1')->create(['channel_id' => $channel->id]);

    (new Api51xDriver($channel))->syncStatus($message);

    expect($message->fresh()->status)->toBe(MessageStatus::Error);
});

it('cancela el mensaje en el proveedor', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $channel = api51xChannel();
    $message = Message::factory()->enCola('msg-remoto-1')->create(['channel_id' => $channel->id]);

    expect((new Api51xDriver($channel))->cancel($message))->toBeTrue();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://51x.test/api/messages/msg-remoto-1/cancel');
});

it('devuelve false cuando el proveedor ya despacho el mensaje', function () {
    Http::fake(['*' => Http::response(['error' => 'ya despachado'], 409)]);

    $channel = api51xChannel();
    $message = Message::factory()->enCola('msg-remoto-1')->create(['channel_id' => $channel->id]);

    expect((new Api51xDriver($channel))->cancel($message))->toBeFalse();
});
