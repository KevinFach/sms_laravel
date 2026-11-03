<?php

use App\Models\Client;
use App\Models\Message;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('forbids v1 access to users without a linked client', function () {
    Message::create(['mensaje' => 'Ajeno', 'numero' => '+529990000000', 'client_id' => Client::factory()->create()->id]);
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/messages')->assertForbidden()->assertJsonStructure(['error']);
    $this->postJson('/api/v1/messages', ['mensaje' => 'Hola', 'numero' => '9991234567'])->assertForbidden();

    expect(Message::count())->toBe(1);
});

it('only lists the messages of the linked client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    Message::create(['mensaje' => 'Mío', 'numero' => '+529991234567', 'client_id' => $client->id]);
    Message::create(['mensaje' => 'Ajeno', 'numero' => '+529990000000', 'client_id' => Client::factory()->create()->id]);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/messages')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.mensaje', 'Mío');
});
