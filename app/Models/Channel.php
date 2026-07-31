<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

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

    /** El canal predeterminado del sistema (fallback cuando un cliente no tiene uno asignado). */
    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first();
    }
}
