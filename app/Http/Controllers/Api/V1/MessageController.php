<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MessageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkMessageRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Client;
use App\Models\Message;
use Illuminate\Http\Request;

/**
 * API del plano desarrollador. Autenticada con token Sanctum del usuario.
 * Los mensajes se aíslan por el cliente asociado al usuario autenticado.
 */
class MessageController extends Controller
{
    /** Crear un mensaje individual. */
    public function store(StoreMessageRequest $request)
    {
        $client = $this->resolveClient($request);
        $data = $request->validated();

        $msg = Message::create([
            'nombre' => $data['nombre'] ?? null,
            'mensaje' => $data['mensaje'],
            'numero' => $data['numero'],
            'client_id' => $client?->id,
            'fecha_envio' => $data['fecha_envio'] ?? null,
            'hora_envio' => $data['hora_envio'] ?? null,
        ]);

        return response()->json([
            'message' => 'Mensaje creado correctamente',
            'msg_id' => $msg->msg_id,
            'status' => $msg->status->value,
        ], 201);
    }

    /** Crear muchos mensajes de una sola llamada (envío masivo). */
    public function bulk(BulkMessageRequest $request)
    {
        $client = $this->resolveClient($request);
        $data = $request->validated();
        $created = [];

        foreach ($data['mensajes'] as $item) {
            $msg = Message::create([
                'nombre' => $item['nombre'] ?? null,
                'mensaje' => $item['mensaje'],
                'numero' => $item['numero'],
                'client_id' => $client?->id,
                'fecha_envio' => $item['fecha_envio'] ?? $data['fecha_envio'] ?? null,
                'hora_envio' => $item['hora_envio'] ?? $data['hora_envio'] ?? null,
            ]);

            $created[] = [
                'msg_id' => $msg->msg_id,
                'numero' => $item['numero'],
                'status' => $msg->status->value,
            ];
        }

        return response()->json([
            'message' => count($created).' mensajes creados',
            'data' => $created,
        ], 201);
    }

    /** Estado de un mensaje. */
    public function show(Request $request, string $msg_id)
    {
        $mensaje = $this->scopedQuery($request)->where('msg_id', $msg_id)->first();

        if (! $mensaje) {
            return response()->json(['error' => 'Mensaje no encontrado'], 404);
        }

        return response()->json($this->present($mensaje));
    }

    /** Listado paginado con filtro opcional por status. */
    public function index(Request $request)
    {
        $query = $this->scopedQuery($request);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $mensajes = $query->latest()->paginate(50);

        return response()->json(
            $mensajes->through(fn (Message $m) => $this->present($m))
        );
    }

    /** Cancelar un mensaje que aún no ha sido enviado. */
    public function cancel(Request $request, string $msg_id)
    {
        $mensaje = $this->scopedQuery($request)->where('msg_id', $msg_id)->first();

        if (! $mensaje) {
            return response()->json(['error' => 'Mensaje no encontrado'], 404);
        }

        try {
            $mensaje->transitionTo(MessageStatus::Cancelado);
        } catch (\DomainException) {
            return response()->json([
                'error' => 'No se puede cancelar un mensaje ya enviado o finalizado.',
            ], 422);
        }

        return response()->json([
            'message' => 'Mensaje cancelado',
            'status' => $mensaje->status->value,
        ]);
    }

    /* -------------------------------------------------------------------
     |  Helpers
     |------------------------------------------------------------------ */

    private function resolveClient(Request $request): ?Client
    {
        $user = $request->user();

        return $user ? Client::where('user_id', $user->id)->first() : null;
    }

    /** Query de mensajes aislada al cliente autenticado (si tiene uno). */
    private function scopedQuery(Request $request)
    {
        $client = $this->resolveClient($request);
        $query = Message::query();

        if ($client) {
            $query->where('client_id', $client->id);
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Message $m): array
    {
        return [
            'msg_id' => $m->msg_id,
            'nombre' => $m->nombre,
            'numero' => $m->numero,
            'mensaje' => $m->mensaje,
            'status' => $m->status->value,
            'status_label' => $m->status->label(),
            'fecha_envio' => $m->fecha_envio?->format('Y-m-d'),
            'hora_envio' => $m->hora_envio,
            'sent_at' => $m->sent_at?->toDateTimeString(),
            'error' => $m->error_message,
        ];
    }
}
