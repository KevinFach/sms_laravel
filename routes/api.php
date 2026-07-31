<?php

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
| Rutas legacy (compatibilidad con el ESP32 ya desplegado)
|--------------------------------------------------------------------------
*/
Route::middleware('api.token')->group(function () {
    Route::match(['get', 'post'], '/mensaje/{id}/procesado', [MensajeController::class, 'marcarComoProcesado']);
    Route::get('/pendientes', [MensajeController::class, 'pendientes']);
    Route::post('/mensaje/crear', [MensajeController::class, 'crear']);
    Route::get('/mensajes/status/{msg_id}', [MensajeController::class, 'status']);
});
