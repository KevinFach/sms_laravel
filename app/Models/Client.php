<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
