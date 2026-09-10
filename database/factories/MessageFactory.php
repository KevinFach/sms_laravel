<?php

namespace Database\Factories;

use App\Enums\MessageStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'mensaje' => fake()->sentence(),
            'numero' => '+52'.fake()->numerify('##########'),
            'fecha' => now(),
        ];
    }

    /** Mensaje programado a futuro (el dispatcher no debe tocarlo todavía). */
    public function programado(?string $fecha = null, ?string $hora = null): static
    {
        $momento = now()->addDay();

        return $this->state([
            'status' => MessageStatus::Programado,
            'fecha_envio' => $fecha ?? $momento->toDateString(),
            'hora_envio' => $hora ?? $momento->format('H:i:s'),
        ]);
    }

    /** Mensaje ya despachado a un canal remoto, esperando confirmación. */
    public function enCola(?string $externalId = null): static
    {
        return $this->state([
            'status' => MessageStatus::EnCola,
            'external_id' => $externalId,
        ]);
    }
}
