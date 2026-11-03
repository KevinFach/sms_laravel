<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\UsageRequest;
use App\Models\Channel;
use App\Models\Client;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * Consumo del cliente de la API key en un rango de fechas (por fecha de creación del mensaje).
 */
class UsageController extends Controller
{
    private const array INTEGRATION_STATUSES = ['scheduled', 'pending', 'sent', 'failed', 'cancelled'];

    public function __invoke(UsageRequest $request): JsonResponse
    {
        /** @var Client $client */
        $client = $request->attributes->get('client');

        $timezone = config('sms.timezone');
        $from = $request->date('from', 'Y-m-d', $timezone)?->startOfDay() ?? Carbon::now($timezone)->startOfMonth();
        $to = $request->date('to', 'Y-m-d', $timezone)?->endOfDay() ?? Carbon::now($timezone)->endOfDay();

        $rows = Message::query()
            ->where('client_id', $client->id)
            ->whereBetween('created_at', [$from->copy()->utc(), $to->copy()->utc()])
            ->selectRaw('channel_id, status, COUNT(*) as total')
            ->groupBy('channel_id', 'status')
            ->get();

        $emptyStatuses = array_fill_keys(self::INTEGRATION_STATUSES, 0);
        $byStatus = $emptyStatuses;
        $byDevice = [];

        $channelNames = Channel::query()
            ->whereIn('id', $rows->pluck('channel_id')->filter()->unique())
            ->pluck('nombre', 'id');

        foreach ($rows as $row) {
            $status = $row->status->toIntegration();
            $total = (int) $row->total;
            $byStatus[$status] += $total;

            $deviceKey = (string) ($row->channel_id ?? 'none');
            $byDevice[$deviceKey] ??= [
                'device_id' => $row->channel_id ? (string) $row->channel_id : null,
                'name' => $row->channel_id ? $channelNames->get($row->channel_id) : null,
                'total' => 0,
                'by_status' => $emptyStatuses,
            ];
            $byDevice[$deviceKey]['total'] += $total;
            $byDevice[$deviceKey]['by_status'][$status] += $total;
        }

        return response()->json([
            'client' => ['id' => $client->id, 'name' => $client->nombre],
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'total' => array_sum($byStatus),
            'by_status' => $byStatus,
            'by_device' => array_values($byDevice),
        ]);
    }
}
