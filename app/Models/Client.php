<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cliente del SaaS. Puede tener un canal asignado; si no, usa el canal predeterminado.
 */
class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'email',
        'status',
        'channel_id',
        'user_id',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    /** Dispositivos asignados explícitamente al cliente (puede tener varios). */
    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class)->withTimestamps();
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ClientApiKey::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Canal efectivo: el asignado al cliente o, en su defecto, el predeterminado global. */
    public function effectiveChannel(): ?Channel
    {
        return $this->channel ?: Channel::default();
    }

    /**
     * Dispositivos que el cliente puede usar: los asignados por pivote; si no tiene,
     * su canal efectivo (asignado o predeterminado).
     *
     * @return Collection<int, Channel>
     */
    public function allowedChannels(): Collection
    {
        $channels = $this->channels()->orderBy('channels.id')->get();

        if ($channels->isNotEmpty()) {
            return $channels;
        }

        $effective = $this->effectiveChannel();

        return new Collection($effective ? [$effective] : []);
    }

    public function isActivo(): bool
    {
        return $this->status === 'activo';
    }
}
