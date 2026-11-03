<?php

namespace App\Http\Requests\Integration;

use App\Models\Client;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreIntegrationMessageRequest extends FormRequest
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
        /** @var Client $client */
        $client = $this->attributes->get('client');

        $allowedDeviceIds = $client->allowedChannels()
            ->filter->isActivo()
            ->map(fn ($channel): string => (string) $channel->id)
            ->values()
            ->all();

        return [
            'to' => ['required', 'string', 'max:20'],
            'channel' => ['nullable', 'string', Rule::in(['sms'])],
            'body' => ['required', 'string', 'max:1000'],
            'device_id' => ['required', Rule::in($allowedDeviceIds)],
            'fecha_envio' => ['nullable', 'date_format:Y-m-d', 'required_with:hora_envio'],
            'hora_envio' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'external_ref' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to.required' => 'El destinatario (to) es obligatorio.',
            'body.required' => 'El cuerpo del mensaje (body) es obligatorio.',
            'body.max' => 'El mensaje no puede exceder 1000 caracteres.',
            'device_id.required' => 'El dispositivo (device_id) es obligatorio.',
            'device_id.in' => 'El dispositivo no existe, está inactivo o no está asignado a esta API key.',
            'channel.in' => 'Solo se admite el canal sms.',
            'fecha_envio.date_format' => 'fecha_envio debe tener el formato Y-m-d.',
            'fecha_envio.required_with' => 'fecha_envio es obligatoria cuando se envía hora_envio.',
            'hora_envio.regex' => 'hora_envio debe tener el formato HH:MM.',
        ];
    }

    /**
     * Respuesta de error con la clave `error`, que es la que leen los integradores.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'error' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}
