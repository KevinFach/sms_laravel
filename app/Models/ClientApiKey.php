<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * API key con la que un sistema externo (p.ej. notifica_ya) actúa en nombre de un Client.
 * Solo se guarda el hash sha256; la key en claro se muestra una única vez al emitirla.
 */
class ClientApiKey extends Model
{
    /** @use HasFactory<\Database\Factories\ClientApiKeyFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'prefix',
        'key_hash',
        'last_used_at',
        'revoked_at',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Emite una nueva key para el cliente y devuelve el valor en claro.
     */
    public static function issue(Client $client, string $name): string
    {
        $plainKey = 'sk_'.Str::random(40);

        $client->apiKeys()->create([
            'name' => $name,
            'prefix' => substr($plainKey, 0, 10),
            'key_hash' => static::hashKey($plainKey),
        ]);

        return $plainKey;
    }

    public static function hashKey(string $plainKey): string
    {
        return hash('sha256', $plainKey);
    }

    public static function findActiveByPlainKey(string $plainKey): ?self
    {
        return static::query()
            ->active()
            ->where('key_hash', static::hashKey($plainKey))
            ->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }
}
