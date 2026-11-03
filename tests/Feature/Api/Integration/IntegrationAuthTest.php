<?php

use App\Models\Client;
use App\Models\ClientApiKey;

it('rejects requests without an api key', function () {
    $this->getJson('/api/devices')
        ->assertUnauthorized()
        ->assertJsonPath('error', 'Falta la API key (header X-API-Key).');
});

it('rejects an unknown api key', function () {
    $this->getJson('/api/devices', ['X-API-Key' => 'sk_nope'])
        ->assertUnauthorized()
        ->assertJsonStructure(['error']);
});

it('rejects a revoked api key', function () {
    $client = Client::factory()->create();
    $plainKey = ClientApiKey::issue($client, 'notifica_ya');
    $client->apiKeys()->first()->revoke();

    $this->getJson('/api/devices', ['X-API-Key' => $plainKey])->assertUnauthorized();
});

it('rejects a key whose client is inactive', function () {
    $client = Client::factory()->inactive()->create();
    $plainKey = ClientApiKey::issue($client, 'notifica_ya');

    $this->getJson('/api/devices', ['X-API-Key' => $plainKey])->assertForbidden();
});

it('accepts a valid key and records its last use', function () {
    $client = Client::factory()->create();
    $plainKey = ClientApiKey::issue($client, 'notifica_ya');

    $this->getJson('/api/devices', ['X-API-Key' => $plainKey])->assertOk();

    expect($client->apiKeys()->first()->last_used_at)->not->toBeNull();
});

it('stores only the hash of the issued key', function () {
    $client = Client::factory()->create();
    $plainKey = ClientApiKey::issue($client, 'notifica_ya');
    $apiKey = $client->apiKeys()->first();

    expect($plainKey)->toStartWith('sk_')
        ->and($apiKey->key_hash)->toBe(hash('sha256', $plainKey))
        ->and($apiKey->prefix)->toBe(substr($plainKey, 0, 10));
});
