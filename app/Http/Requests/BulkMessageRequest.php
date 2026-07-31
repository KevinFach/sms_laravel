<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Envío masivo: una programación global opcional + un arreglo de mensajes,
     * cada uno con su propia programación opcional que tiene prioridad.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fecha_envio' => ['nullable', 'date_format:Y-m-d'],
            'hora_envio' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'mensajes' => ['required', 'array', 'min:1'],
            'mensajes.*.nombre' => ['nullable', 'string', 'max:255'],
            'mensajes.*.mensaje' => ['required', 'string'],
            'mensajes.*.numero' => ['required', 'string', 'max:20'],
            'mensajes.*.fecha_envio' => ['nullable', 'date_format:Y-m-d'],
            'mensajes.*.hora_envio' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        ];
    }
}
