<?php

use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\RelationManagers\ApiKeysRelationManager;
use App\Models\Client;
use App\Models\ClientApiKey;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('generates an api key for the client', function () {
    $client = Client::factory()->create();

    Livewire::test(ApiKeysRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
        ->callAction(TestAction::make('generate')->table(), ['name' => 'notifica_ya - Juan'])
        ->assertHasNoFormErrors()
        ->assertNotified('API key generada');

    expect($client->apiKeys()->count())->toBe(1)
        ->and($client->apiKeys()->first()->name)->toBe('notifica_ya - Juan');
});

it('revokes an api key', function () {
    $client = Client::factory()->create();
    ClientApiKey::issue($client, 'old');
    $apiKey = $client->apiKeys()->first();

    Livewire::test(ApiKeysRelationManager::class, ['ownerRecord' => $client, 'pageClass' => EditClient::class])
        ->assertCanSeeTableRecords([$apiKey])
        ->callAction(TestAction::make('revoke')->table($apiKey));

    expect($apiKey->refresh()->isRevoked())->toBeTrue();
});
