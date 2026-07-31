<?php

namespace App\Http\Controllers;

use App\Enums\MessageStatus;
use App\Models\Message;
use Illuminate\Http\Request;

class MensajeController extends Controller
{
    public function pendientes()
    {
        // Mensajes listos para salir (en cola o vencidos sin encolar todavía).
        $mensajes = Message::sendable()->get(['id', 'mensaje', 'numero', 'estatus', 'fecha']);

        // Construir texto en formato pipeline
        $salida = '';
        foreach ($mensajes as $m) {
            $salida .= "{$m->id}|{$m->numero}|{$m->mensaje}\n";
        }

        // Devolver texto plano
        return response($salida, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function marcarComoProcesado(int $id)
    {
        // 1. Encontrar el mensaje por su ID
        $mensaje = Message::find($id);

        // 2. Verificar si el mensaje existe
        if (! $mensaje) {
            return response()->json([
                'error' => 'Mensaje no encontrado.',
            ], 404);
        }

        // 3. Marcar como enviado a través de la máquina de estados (si es posible)
        try {
            $mensaje->transitionTo(MessageStatus::Enviado);
        } catch (\DomainException) {
            // Ya estaba en un estado final (enviado/cancelado): no-op idempotente.
        }

        // 4. Devolver una respuesta de éxito
        return response()->json([
            'message' => "Mensaje con ID {$id} marcado como procesado.",
        ], 200);
    }

    public function crear(Request $request)
    {
        $validated = $request->validate([
            'mensaje' => 'required|string',
            'numero' => 'required|string',
        ]);

        $msg = Message::create([
            'mensaje' => $validated['mensaje'],
            'numero' => $validated['numero'],
            'estatus' => 0,
            'fecha' => now(),
        ]);

        return response()->json([
            'message' => 'Mensaje creado correctamente',
            'msg_id' => $msg->msg_id, // ← importante
            'data' => $msg,
        ], 201);
    }

    public function status($msg_id)
    {
        $mensaje = Message::where('msg_id', $msg_id)->first();

        if (! $mensaje) {
            return response()->json([
                'error' => 'Mensaje no encontrado',
            ], 404);
        }

        return response()->json([
            'msg_id' => $mensaje->msg_id,
            'estatus' => $mensaje->estatus, // 0 = pendiente, 1 = enviado (legacy)
            'status' => $mensaje->status->value, // máquina de 6 estados
            'mensaje' => $mensaje->mensaje,
            'numero' => $mensaje->numero,
        ], 200);
    }
}
