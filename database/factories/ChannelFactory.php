<?php

namespace Database\Factories;

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
            'clave' => Str::random(40),
            'tipo' => 'esp32',
            'nombre' => 'ESP32 '.fake()->unique()->numerify('###'),
            'telefono' => fake()->numerify('##########'),
            'status' => 'activo',
            'is_default' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => 'inactivo']);
    }

    public function asDefault(): static
    {
        return $this->state(fn (): array => ['is_default' => true]);
    }

    public function online(): static
    {
        return $this->state(fn (): array => ['last_seen_at' => now()]);
    }
}
