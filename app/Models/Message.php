<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Message extends Model
{
    /** @use HasFactory<\Database\Factories\MessageFactory> */
    use HasFactory;

    protected $fillable = [
        'mensaje',
        'numero',
        'estatus',
        'fecha',
        'msg_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            // Generar msg_id único
            if (empty($model->msg_id)) {
                $model->msg_id = Str::uuid();
            }

            // Establecer fecha automáticamente si no viene del formulario
            if (empty($model->fecha)) {
                $model->fecha = Carbon::now();
            }

            // Estatus inicial por defecto
            if (is_null($model->estatus)) {
                $model->estatus = 0;
            }
        });
    }

    protected $casts = [
        'mensaje' => 'encrypted',
        'numero'  => 'encrypted',
    ];
}
