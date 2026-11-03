<?php

use App\Enums\MessageStatus;
use App\Models\Channel;
use App\Models\Client;
use App\Models\ClientApiKey;
use App\Models\Message;

function makeMessage(Client $client, ?Channel $channel, MessageStatus $status, array $attributes = []): Message
{
    return Message::create(array_merge([
        'mensaje' => 'Hola',
        'numero' => '+529991234567',
        'client_id' => $client->id,
        'channel_id' => $channel?->id,
        'status' => $status,
    ], $attributes));
}

it('summarizes the client usage by status and device', function () {
    $client = Client::factory()->create(['nombre' => 'Juan']);
    $a = Channel::factory()->create(['nombre' => 'ESP32 A']);
    $b = Channel::factory()->create(['nombre' => 'ESP32 B']);
    $client->channels()->attach([$a->id, $b->id]);

    makeMessage($client, $a, MessageStatus::Enviado);
    makeMessage($client, $a, MessageStatus::Enviado);
    makeMessage($client, $a, MessageStatus::Error);
    makeMessage($client, $b, MessageStatus::EnCola);
    makeMessage(Client::factory()->create(), $a, MessageStatus::Enviado);

    $response = $this->getJson('/api/usage', ['X-API-Key' => ClientApiKey::issue($client, 'test')]);

    $response->assertOk()
        ->assertJsonPath('client.name', 'Juan')
        ->assertJsonPath('total', 4)
        ->assertJsonPath('by_status.sent', 2)
        ->assertJsonPath('by_status.failed', 1)
        ->assertJsonPath('by_status.pending', 1)
        ->assertJsonPath('by_status.cancelled', 0)
        ->assertJsonCount(2, 'by_device');

    $deviceA = collect($response->json('by_device'))->firstWhere('device_id', (string) $a->id);
    expect($deviceA['name'])->toBe('ESP32 A')
        ->and($deviceA['total'])->toBe(3)
        ->and($deviceA['by_status']['sent'])->toBe(2);
});

it('filters usage by date range', function () {
    $client = Client::factory()->create();
    $channel = Channel::factory()->create();
    $client->channels()->attach($channel);

    makeMessage($client, $channel, MessageStatus::Enviado)->forceFill(['created_at' => now()->subMonths(2)])->save();
    makeMessage($client, $channel, MessageStatus::Enviado);

    $headers = ['X-API-Key' => ClientApiKey::issue($client, 'test')];
    $from = now()->subMonths(3)->format('Y-m-d');
    $to = now()->format('Y-m-d');

    $this->getJson("/api/usage?from={$from}&to={$to}", $headers)->assertOk()->assertJsonPath('total', 2);
    $this->getJson('/api/usage', $headers)->assertOk()->assertJsonPath('total', 1);
});

it('validates the date range', function () {
    $client = Client::factory()->create();

    $this->getJson('/api/usage?from=2026-05-10&to=2026-05-01', ['X-API-Key' => ClientApiKey::issue($client, 'test')])
        ->assertUnprocessable()
        ->assertJsonPath('error', 'to debe ser igual o posterior a from.');
});
