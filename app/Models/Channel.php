<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Canal de envío = un servidor/dispositivo (p.ej. gateway ESP32) que expulsa los SMS.
 */
class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'clave',
        'tipo',
        'nombre',
        'telefono',
        'status',
        'is_default',
        'last_seen_at',
        'last_ip',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    /** Clientes que tienen este canal asignado vía pivote (multi-dispositivo). */
    public function assignedClients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class)->withTimestamps();
    }

    public function scopeActivo($query)
    {
        return $query->where('status', 'activo');
    }

    public function isActivo(): bool
    {
        return $this->status === 'activo';
    }

    /** El dispositivo reportó actividad dentro de la ventana configurada. */
    public function isConnected(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gte(now()->subSeconds(config('sms.device_online_seconds')));
    }

    /**
     * Registra un latido del dispositivo. Solo escribe si pasaron más de 30 s desde el
     * último, para no hacer un UPDATE en cada poll del ESP32.
     */
    public function recordHeartbeat(?string $ip): void
    {
        if ($this->last_seen_at !== null && $this->last_seen_at->gt(now()->subSeconds(30)) && $this->last_ip === $ip) {
            return;
        }

        $this->forceFill(['last_seen_at' => now(), 'last_ip' => $ip])->saveQuietly();
    }

    /** El canal predeterminado del sistema (fallback cuando un cliente no tiene uno asignado). */
    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first();
    }
}
