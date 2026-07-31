<?php

namespace App\Models;

use App\Enums\MessageStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Message extends Model
{
    /** @use HasFactory<\Database\Factories\MessageFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'mensaje',
        'numero',
        'estatus',
        'status',
        'fecha',
        'msg_id',
        'client_id',
        'channel_id',
        'error_message',
        'sent_at',
        'fecha_evento',
        'hora_evento',
        'fecha_envio',
        'hora_envio',
    ];

    protected $casts = [
        'mensaje' => 'encrypted',
        'numero' => 'encrypted',
        'status' => MessageStatus::class,
        'fecha_evento' => 'date',
        'fecha_envio' => 'date',
        'sent_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Message $model) {
            // Generar msg_id único
            if (empty($model->msg_id)) {
                $model->msg_id = Str::uuid();
            }

            // Fecha de creación automática si no viene del formulario
            if (empty($model->fecha)) {
                $model->fecha = Carbon::now();
            }

            // Estado inicial según programación
            if (is_null($model->status)) {
                $model->status = $model->hasFutureSchedule()
                    ? MessageStatus::Programado
                    : MessageStatus::PorEnviar;
            }

            // Compatibilidad legacy: derivar el boolean `estatus`
            if (is_null($model->estatus)) {
                $model->estatus = $model->status === MessageStatus::Enviado ? 1 : 0;
            }
        });
    }

    /* -------------------------------------------------------------------
     |  Relaciones
     |------------------------------------------------------------------ */

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    /* -------------------------------------------------------------------
     |  Programación
     |------------------------------------------------------------------ */

    /** Momento programado de envío (fecha + hora), o null si no hay programación. */
    public function scheduledAt(): ?Carbon
    {
        if (is_null($this->fecha_envio)) {
            return null;
        }

        return Carbon::parse(
            $this->fecha_envio->format('Y-m-d').' '.($this->hora_envio ?? '00:00:00')
        );
    }

    public function hasFutureSchedule(): bool
    {
        $scheduled = $this->scheduledAt();

        return $scheduled !== null && $scheduled->isFuture();
    }

    /* -------------------------------------------------------------------
     |  Máquina de estados
     |------------------------------------------------------------------ */

    /**
     * Transición validada entre estados. Lanza \DomainException si es ilegal.
     * Sincroniza campos derivados (`estatus`, `sent_at`).
     */
    public function transitionTo(MessageStatus $to): bool
    {
        $from = $this->status;

        if ($from === $to) {
            return true;
        }

        if (! $from->canTransitionTo($to)) {
            throw new \DomainException("Transición no permitida: {$from->value} → {$to->value}");
        }

        $this->status = $to;

        // Sincronizar campos derivados / compatibilidad legacy
        if ($to === MessageStatus::Enviado) {
            $this->estatus = 1;
            if (is_null($this->sent_at)) {
                $this->sent_at = now();
            }
        } elseif (in_array($to, [MessageStatus::PorEnviar, MessageStatus::EnCola, MessageStatus::Programado], true)) {
            $this->estatus = 0;
        }

        return $this->save();
    }

    /* -------------------------------------------------------------------
     |  Scopes
     |------------------------------------------------------------------ */

    /**
     * Mensajes candidatos a encolarse: por_enviar/programado cuya hora ya llegó
     * (o sin programación). Entrada del dispatcher.
     */
    public function scopeDue($query)
    {
        return $query
            ->whereIn('status', [MessageStatus::PorEnviar->value, MessageStatus::Programado->value])
            ->where(function ($q) {
                $q->whereNull('fecha_envio')
                    ->orWhereRaw("TIMESTAMP(fecha_envio, COALESCE(hora_envio, '00:00:00')) <= NOW()");
            });
    }

    /**
     * Mensajes que un canal puede jalar ahora mismo: en_cola, o due sin encolar
     * todavía (tolerante a que el dispatcher no haya corrido). Usado por rutas legacy.
     */
    public function scopeSendable($query)
    {
        return $query->where(function ($q) {
            $q->where('status', MessageStatus::EnCola->value)
                ->orWhere(function ($qq) {
                    $qq->whereIn('status', [MessageStatus::PorEnviar->value, MessageStatus::Programado->value])
                        ->where(function ($q3) {
                            $q3->whereNull('fecha_envio')
                                ->orWhereRaw("TIMESTAMP(fecha_envio, COALESCE(hora_envio, '00:00:00')) <= NOW()");
                        });
                });
        });
    }

    /** Alias de compatibilidad (código previo llamaba a readyToSend). */
    public function scopeReadyToSend($query)
    {
        return $this->scopeSendable($query);
    }
}
