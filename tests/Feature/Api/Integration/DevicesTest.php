<?php

use App\Models\Channel;
use App\Models\Client;
use App\Models\ClientApiKey;

it('lists only the devices assigned to the client', function () {
    $client = Client::factory()->create();
    $mine = Channel::factory()->online()->create(['nombre' => 'ESP32 Oficina']);
    Channel::factory()->create(['nombre' => 'ESP32 Ajeno']);
    $client->channels()->attach($mine);
    $plainKey = ClientApiKey::issue($client, 'test');

    $this->getJson('/api/devices', ['X-API-Key' => $plainKey])
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', (string) $mine->id)
        ->assertJsonPath('0.name', 'ESP32 Oficina')
        ->assertJsonPath('0.active', true)
        ->assertJsonPath('0.connected', true)
        ->assertJsonStructure([['id', 'name', 'active', 'connected', 'created_at', 'last_seen_at']]);
});

it('falls back to the default channel when the client has no devices assigned', function () {
    $default = Channel::factory()->asDefault()->create();
    $client = Client::factory()->create();
    $plainKey = ClientApiKey::issue($client, 'test');

    $this->getJson('/api/devices', ['X-API-Key' => $plainKey])
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', (string) $default->id);
});

it('reports a device as disconnected when it has not been seen recently', function () {
    $client = Client::factory()->create();
    $stale = Channel::factory()->create(['last_seen_at' => now()->subMinutes(10)]);
    $never = Channel::factory()->create();
    $client->channels()->attach([$stale->id, $never->id]);
    $plainKey = ClientApiKey::issue($client, 'test');

    $this->getJson('/api/devices', ['X-API-Key' => $plainKey])
        ->assertOk()
        ->assertJsonPath('0.connected', false)
        ->assertJsonPath('1.connected', false)
        ->assertJsonPath('1.last_seen_at', null);
});

it('records a heartbeat when the esp32 polls for pending messages', function () {
    $channel = Channel::factory()->create();

    $this->get('/api/v1/gateway/pendientes?clave='.$channel->clave)->assertOk();

    $channel->refresh();
    expect($channel->last_seen_at)->not->toBeNull()
        ->and($channel->isConnected())->toBeTrue()
        ->and($channel->last_ip)->toBe('127.0.0.1');
});

it('does not rewrite the heartbeat on every poll', function () {
    $seenAt = now()->subSeconds(10)->startOfSecond();
    $channel = Channel::factory()->create(['last_seen_at' => $seenAt, 'last_ip' => '127.0.0.1']);

    $this->get('/api/v1/gateway/pendientes?clave='.$channel->clave)->assertOk();

    expect($channel->refresh()->last_seen_at->equalTo($seenAt))->toBeTrue();
});
