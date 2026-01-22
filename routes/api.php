<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MensajeController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/mensaje/{id}/procesado', [MensajeController::class, 'marcarComoProcesado']);

Route::middleware('api.token')->group(function () {
    // La ruta final será: /api/pendientes
    Route::get('/pendientes', [MensajeController::class, 'pendientes']);
    Route::post('/mensaje/crear', [MensajeController::class, 'crear']);
    Route::get('/mensajes/status/{msg_id}', [MensajeController::class, 'status']);
});
