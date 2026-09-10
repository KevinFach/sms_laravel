<?php

use App\Enums\MessageStatus;
use App\Models\Channel;
use App\Models\Message;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('encola los mensajes de un canal esp32 sin hacer llamadas http', function () {
    Http::fake();

    $channel = Channel::factory()->esp32()->predeterminado()->create();
    $message = Message::factory()->create();

    $this->artisan('messages:dispatch')->assertSuccessful();

    $message->refresh();

    expect($message->status)->toBe(MessageStatus::EnCola)
        ->and($message->channel_id)->toBe($channel->id)
        ->and($message->external_id)->toBeNull();

    Http::assertNothingSent();
});

it('empuja a 51x los mensajes de un canal push', function () {
    Http::fake(['*' => Http::response(['id' => 'msg-remoto-1'], 202)]);

    $channel = Channel::factory()->api51x()->predeterminado()->create();
    $message = Message::factory()->create();

    $this->artisan('messages:dispatch')->assertSuccessful();

    $message->refresh();

    expect($message->status)->toBe(MessageStatus::EnCola)
        ->and($message->channel_id)->toBe($channel->id)
        ->and($message->external_id)->toBe('msg-remoto-1');

    Http::assertSentCount(1);
});

it('no empuja los mensajes programados a futuro', function () {
    Http::fake();

    Channel::factory()->api51x()->predeterminado()->create();
    $message = Message::factory()->programado()->create();

    $this->artisan('messages:dispatch')->assertSuccessful();

    expect($message->fresh()->status)->toBe(MessageStatus::Programado);

    Http::assertNothingSent();
});

it('empuja un mensaje programado cuya hora ya vencio', function () {
    Http::fake(['*' => Http::response(['id' => 'msg-remoto-1'], 202)]);

    Channel::factory()->api51x()->predeterminado()->create();
    $message = Message::factory()
        ->programado(now()->subDay()->toDateString(), '08:00:00')
        ->create();

    $this->artisan('messages:dispatch')->assertSuccessful();

    expect($message->fresh()->status)->toBe(MessageStatus::EnCola);
});

it('un canal caido no impide despachar el resto del lote', function () {
    $canalRoto = Channel::factory()->api51x(['base_url' => 'https://roto.test'])->create();
    $canalBueno = Channel::factory()->esp32()->predeterminado()->create();

    Http::fake([
        'roto.test/*' => Http::response(['error' => 'sin dispositivo'], 500),
    ]);

    $fallido = Message::factory()->create(['channel_id' => $canalRoto->id]);
    $ok = Message::factory()->create();

    $this->artisan('messages:dispatch')->assertSuccessful();

    expect($fallido->fresh()->status)->toBe(MessageStatus::Error)
        ->and($ok->fresh()->status)->toBe(MessageStatus::EnCola)
        ->and($ok->fresh()->channel_id)->toBe($canalBueno->id);
});

it('marca error cuando no hay ningun canal activo', function () {
    Channel::factory()->esp32()->predeterminado()->inactivo()->create();
    $message = Message::factory()->create();

    $this->artisan('messages:dispatch')->assertSuccessful();

    expect($message->fresh()->status)->toBe(MessageStatus::Error);
});

it('confirma contra 51x los mensajes ya despachados', function () {
    Http::fake(['*' => Http::response(['status' => 'sent'], 200)]);

    $channel = Channel::factory()->api51x()->create();
    $message = Message::factory()->enCola('msg-remoto-1')->create(['channel_id' => $channel->id]);

    $this->artisan('messages:sync-remote')->assertSuccessful();

    expect($message->fresh()->status)->toBe(MessageStatus::Enviado)
        ->and($message->fresh()->sent_at)->not->toBeNull();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://51x.test/api/messages/msg-remoto-1');
});

it('no consulta el remoto para los mensajes de canales pull', function () {
    Http::fake();

    $channel = Channel::factory()->esp32()->create();
    Message::factory()->enCola()->create(['channel_id' => $channel->id]);

    $this->artisan('messages:sync-remote')->assertSuccessful();

    Http::assertNothingSent();
});
