<?php

use App\Enums\MessageStatus;
use App\Models\User;

test('la documentación es pública', function () {
    $this->get(route('docs'))
        ->assertOk()
        ->assertSee('Documentación de la API');
});

test('documenta los endpoints de la API v1', function (string $fragmento) {
    $this->get(route('docs'))->assertSee($fragmento, escape: false);
})->with([
    '/api/v1/messages',
    '/api/v1/messages/bulk',
    '/api/v1/messages/{msg_id}',
    'Authorization: Bearer',
    'Accept: application/json',
]);

test('documenta los seis estados del mensaje', function () {
    $response = $this->get(route('docs'));

    foreach (MessageStatus::cases() as $estado) {
        $response->assertSee($estado->value, escape: false);
        $response->assertSee($estado->label(), escape: false);
    }
});

test('la landing enlaza a la documentación', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('docs'), escape: false);
});

test('la documentación invita a iniciar sesión cuando no hay usuario', function () {
    $this->get(route('docs'))->assertSee(route('login'), escape: false);
});

test('la documentación enlaza a los API tokens de un usuario autenticado', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('docs'))->assertSee(route('api-tokens.index'), escape: false);
});

test('la pantalla de API tokens está habilitada', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('api-tokens.index'))->assertOk();
});

test('la pantalla de API tokens exige sesión', function () {
    $this->get(route('api-tokens.index'))->assertRedirect(route('login'));
});
