<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['nullable', 'string', 'max:255'],
            'mensaje' => ['required', 'string'],
            'numero' => ['required', 'string', 'max:20'],
            'fecha_envio' => ['nullable', 'date_format:Y-m-d'],
            'hora_envio' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        ];
    }
}
