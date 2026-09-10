<?php

use App\Enums\ChannelType;
use App\Filament\Resources\Channels\Pages\CreateChannel;
use App\Filament\Resources\Channels\Pages\EditChannel;
use App\Models\Channel;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('ofrece los tres tipos de canal en el formulario', function () {
    Livewire::test(CreateChannel::class)
        ->assertFormFieldExists('tipo', fn ($field): bool => $field->getOptions() === ChannelType::options());
});

it('crea un canal 51x guardando sus credenciales', function () {
    Livewire::test(CreateChannel::class)
        ->fillForm([
            'nombre' => '51x Producción',
            'tipo' => ChannelType::Api51x->value,
            'status' => 'activo',
            'config' => [
                'base_url' => 'https://51x.dev',
                'api_key' => 'clave-secreta',
                'device_id' => 'dev-abc',
                'pais' => '+52',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $channel = Channel::query()->where('nombre', '51x Producción')->sole();

    expect($channel->tipo)->toBe(ChannelType::Api51x)
        ->and($channel->configValue('api_key'))->toBe('clave-secreta')
        ->and($channel->configValue('device_id'))->toBe('dev-abc')
        // La columna es NOT NULL UNIQUE: se autogenera aunque el campo esté oculto.
        ->and($channel->clave)->not->toBeEmpty();
});

it('crea un canal esp32 sin credenciales remotas', function () {
    Livewire::test(CreateChannel::class)
        ->fillForm([
            'nombre' => 'Canal ESP32',
            'tipo' => ChannelType::Esp32->value,
            'clave' => 'token-esp32-de-prueba',
            'status' => 'activo',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $channel = Channel::query()->where('nombre', 'Canal ESP32')->sole();

    expect($channel->tipo)->toBe(ChannelType::Esp32)
        ->and($channel->clave)->toBe('token-esp32-de-prueba')
        ->and($channel->config)->toBeNull();
});

it('carga y actualiza las credenciales al editar un canal 51x', function () {
    $channel = Channel::factory()->api51x(['device_id' => 'dev-viejo'])->create();

    Livewire::test(EditChannel::class, ['record' => $channel->getRouteKey()])
        ->assertFormSet([
            'config.api_key' => 'test-api-key',
            'config.device_id' => 'dev-viejo',
        ])
        ->fillForm(['config.device_id' => 'dev-nuevo'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($channel->fresh()->configValue('device_id'))->toBe('dev-nuevo')
        ->and($channel->fresh()->configValue('api_key'))->toBe('test-api-key');
});
