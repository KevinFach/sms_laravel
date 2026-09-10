<?php

namespace Database\Factories;

use App\Enums\ChannelType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Channel>
 */
class ChannelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Canal '.fake()->unique()->word(),
            'clave' => Str::random(40),
            'tipo' => ChannelType::Esp32,
            'telefono' => null,
            'status' => 'activo',
            'is_default' => false,
        ];
    }

    public function esp32(): static
    {
        return $this->state(['tipo' => ChannelType::Esp32]);
    }

    /**
     * Canal push contra 51x.dev.
     *
     * @param  array<string, mixed>  $config
     */
    public function api51x(array $config = []): static
    {
        return $this->state([
            'tipo' => ChannelType::Api51x,
            'config' => array_merge([
                'base_url' => 'https://51x.test',
                'api_key' => 'test-api-key',
                'device_id' => 'dev-1',
                'pais' => '+52',
            ], $config),
        ]);
    }

    public function inactivo(): static
    {
        return $this->state(['status' => 'inactivo']);
    }

    public function predeterminado(): static
    {
        return $this->state(['is_default' => true]);
    }
}
