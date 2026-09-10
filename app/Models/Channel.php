<?php

namespace App\Models;

use App\Enums\ChannelType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Canal de envío = un servidor/dispositivo (p.ej. gateway ESP32) que expulsa los SMS.
 */
class Channel extends Model
{
    /** @use HasFactory<\Database\Factories\ChannelFactory> */
    use HasFactory;

    protected $fillable = [
        'clave',
        'tipo',
        'config',
        'nombre',
        'telefono',
        'status',
        'is_default',
    ];

    protected $casts = [
        'tipo' => ChannelType::class,
        'config' => 'encrypted:array',
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Channel $channel): void {
            // Los canales push (51x.dev) no exponen el campo en el formulario, pero
            // la columna es NOT NULL UNIQUE.
            if (blank($channel->clave)) {
                $channel->clave = Str::random(40);
            }
        });
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function scopeActivo($query)
    {
        return $query->where('status', 'activo');
    }

    public function isActivo(): bool
    {
        return $this->status === 'activo';
    }

    /**
     * Valor de configuración del canal (credenciales del proveedor remoto).
     *
     * No se llama `config()` a propósito: Eloquent intentaría resolver ese nombre
     * como relación cuando el atributo no está cargado.
     */
    public function configValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /** El canal predeterminado del sistema (fallback cuando un cliente no tiene uno asignado). */
    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first();
    }
}
