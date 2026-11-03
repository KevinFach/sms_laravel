<?php

use App\Enums\MessageStatus;
use App\Models\Channel;
use App\Models\Client;
use App\Models\ClientApiKey;
use App\Models\Message;

beforeEach(function () {
    $this->client = Client::factory()->create();
    $this->device = Channel::factory()->create();
    $this->client->channels()->attach($this->device);
    $this->headers = ['X-API-Key' => ClientApiKey::issue($this->client, 'test')];
});

it('creates an immediate message on the chosen device', function () {
    $response = $this->postJson('/api/messages', [
        'to' => '+529991234567',
        'channel' => 'sms',
        'body' => 'Hola desde notifica_ya',
        'device_id' => (string) $this->device->id,
        'external_ref' => 'user-42',
    ], $this->headers);

    $response->assertCreated()
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('device_id', (string) $this->device->id)
        ->assertJsonPath('scheduled_at', null)
        ->assertJsonStructure(['id', 'message_number', 'status', 'device_id', 'scheduled_at']);

    $message = Message::where('msg_id', $response->json('id'))->firstOrFail();
    expect($message->client_id)->toBe($this->client->id)
        ->and($message->channel_id)->toBe($this->device->id)
        ->and($message->numero)->toBe('+529991234567')
        ->and($message->mensaje)->toBe('Hola desde notifica_ya')
        ->and($message->external_ref)->toBe('user-42')
        ->and($response->json('message_number'))->toBe((string) $message->id);
});

it('creates a scheduled message', function () {
    $date = now()->addDays(2)->format('Y-m-d');

    $this->postJson('/api/messages', [
        'to' => '+529991234567',
        'body' => 'Recordatorio',
        'device_id' => (string) $this->device->id,
        'fecha_envio' => $date,
        'hora_envio' => '09:30',
    ], $this->headers)
        ->assertCreated()
        ->assertJsonPath('status', 'scheduled')
        ->assertJsonPath('scheduled_at', fn (string $value): bool => str_starts_with($value, $date.'T09:30:00'));
});

it('rejects a device that is not assigned to the client', function () {
    $foreign = Channel::factory()->create();

    $this->postJson('/api/messages', [
        'to' => '+529991234567',
        'body' => 'Hola',
        'device_id' => (string) $foreign->id,
    ], $this->headers)
        ->assertUnprocessable()
        ->assertJsonPath('error', 'El dispositivo no existe, está inactivo o no está asignado a esta API key.');
});

it('rejects an inactive device', function () {
    $this->device->update(['status' => 'inactivo']);

    $this->postJson('/api/messages', [
        'to' => '+529991234567',
        'body' => 'Hola',
        'device_id' => (string) $this->device->id,
    ], $this->headers)->assertUnprocessable();
});

it('validates required fields', function (string $field) {
    $payload = ['to' => '+529991234567', 'body' => 'Hola', 'device_id' => (string) $this->device->id];
    unset($payload[$field]);

    $this->postJson('/api/messages', $payload, $this->headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field)
        ->assertJsonStructure(['error']);
})->with(['to', 'body', 'device_id']);

it('shows a message with its integration status', function (MessageStatus $status, string $expected) {
    $message = Message::create([
        'mensaje' => 'Hola',
        'numero' => '+529991234567',
        'client_id' => $this->client->id,
        'channel_id' => $this->device->id,
        'status' => $status,
        'error_message' => $status === MessageStatus::Error ? 'Sin señal' : null,
    ]);

    $this->getJson("/api/messages/{$message->msg_id}", $this->headers)
        ->assertOk()
        ->assertJsonPath('id', (string) $message->msg_id)
        ->assertJsonPath('status', $expected)
        ->assertJsonPath('error', $message->error_message);
})->with([
    'programado' => [MessageStatus::Programado, 'scheduled'],
    'por enviar' => [MessageStatus::PorEnviar, 'pending'],
    'en cola' => [MessageStatus::EnCola, 'pending'],
    'enviado' => [MessageStatus::Enviado, 'sent'],
    'error' => [MessageStatus::Error, 'failed'],
    'cancelado' => [MessageStatus::Cancelado, 'cancelled'],
]);

it('does not expose messages of another client', function () {
    $other = Message::create([
        'mensaje' => 'Ajeno',
        'numero' => '+529990000000',
        'client_id' => Client::factory()->create()->id,
    ]);

    $this->getJson("/api/messages/{$other->msg_id}", $this->headers)->assertNotFound();
    $this->postJson("/api/messages/{$other->msg_id}/cancel", [], $this->headers)->assertNotFound();

    expect($other->refresh()->status)->toBe(MessageStatus::PorEnviar);
});

it('cancels a pending message', function () {
    $message = Message::create(['mensaje' => 'Hola', 'numero' => '+529991234567', 'client_id' => $this->client->id]);

    $this->postJson("/api/messages/{$message->msg_id}/cancel", [], $this->headers)->assertNoContent();

    expect($message->refresh()->status)->toBe(MessageStatus::Cancelado);
});

it('refuses to cancel a message already sent', function () {
    $message = Message::create([
        'mensaje' => 'Hola',
        'numero' => '+529991234567',
        'client_id' => $this->client->id,
        'status' => MessageStatus::Enviado,
    ]);

    $this->postJson("/api/messages/{$message->msg_id}/cancel", [], $this->headers)
        ->assertUnprocessable()
        ->assertJsonStructure(['error']);
});

it('goes end to end through the esp32 gateway', function () {
    $id = $this->postJson('/api/messages', [
        'to' => '+529991234567',
        'body' => 'Hola',
        'device_id' => (string) $this->device->id,
    ], $this->headers)->json('id');

    $message = Message::where('msg_id', $id)->firstOrFail();
    $message->transitionTo(MessageStatus::EnCola);

    $this->get("/api/v1/gateway/messages/{$message->id}/enviado?clave={$this->device->clave}")->assertOk();

    $this->getJson("/api/messages/{$id}", $this->headers)
        ->assertOk()
        ->assertJsonPath('status', 'sent')
        ->assertJsonPath('sent_at', fn (?string $value): bool => $value !== null);
});
