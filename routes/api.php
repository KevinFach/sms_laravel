<?php

use App\Http\Controllers\Api\Integration\DeviceController as IntegrationDeviceController;
use App\Http\Controllers\Api\Integration\MessageController as IntegrationMessageController;
use App\Http\Controllers\Api\Integration\UsageController as IntegrationUsageController;
use App\Http\Controllers\Api\V1\GatewayController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\MensajeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    // Plano desarrollador (clientes) — token Sanctum del usuario.
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/messages', [MessageController::class, 'index']);
        Route::post('/messages', [MessageController::class, 'store']);
        Route::post('/messages/bulk', [MessageController::class, 'bulk']);
        Route::get('/messages/{msg_id}', [MessageController::class, 'show']);
        Route::delete('/messages/{msg_id}', [MessageController::class, 'cancel']);
    });

    // Plano gateway (ESP32) — autenticado por clave de canal.
    Route::middleware('channel.token')->prefix('gateway')->group(function () {
        Route::get('/pendientes', [GatewayController::class, 'pendientes']);
        Route::match(['get', 'post'], '/messages/{id}/enviado', [GatewayController::class, 'enviado']);
        Route::match(['get', 'post'], '/messages/{id}/error', [GatewayController::class, 'error']);
    });
});

/*
|--------------------------------------------------------------------------
| API de integración (sistemas externos como notifica_ya)
|--------------------------------------------------------------------------
| Autenticada con API key de cliente (header X-API-Key). Contrato compatible
| con 51x.dev para que el integrador use el mismo cliente HTTP en ambos.
*/
Route::middleware('client.key')->group(function () {
    Route::get('/devices', [IntegrationDeviceController::class, 'index']);
    Route::post('/messages', [IntegrationMessageController::class, 'store']);
    Route::get('/messages/{id}', [IntegrationMessageController::class, 'show']);
    Route::post('/messages/{id}/cancel', [IntegrationMessageController::class, 'cancel']);
    Route::get('/usage', IntegrationUsageController::class);
});

/*
|--------------------------------------------------------------------------
| Rutas legacy (compatibilidad con el ESP32 ya desplegado)
|--------------------------------------------------------------------------
*/
Route::middleware('api.token')->group(function () {
    Route::match(['get', 'post'], '/mensaje/{id}/procesado', [MensajeController::class, 'marcarComoProcesado']);
    Route::get('/pendientes', [MensajeController::class, 'pendientes']);
    Route::post('/mensaje/crear', [MensajeController::class, 'crear']);
    Route::get('/mensajes/status/{msg_id}', [MensajeController::class, 'status']);
});
