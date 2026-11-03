<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientApiKey>
 */
class ClientApiKeyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plainKey = 'sk_'.Str::random(40);

        return [
            'client_id' => Client::factory(),
            'name' => fake()->word(),
            'prefix' => substr($plainKey, 0, 10),
            'key_hash' => ClientApiKey::hashKey($plainKey),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()]);
    }
}
