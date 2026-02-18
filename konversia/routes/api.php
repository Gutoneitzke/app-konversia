<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsAppController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rotas WhatsApp (sem autenticação - comunicação interna com serviço Go)
Route::prefix('whatsapp')->group(function () {
    Route::post('/event', [WhatsAppController::class, 'receiveEvent']);
});

// Rotas WhatsApp para usuários autenticados
Route::middleware('auth:sanctum')->prefix('whatsapp')->group(function () {
    Route::get('/status', [WhatsAppController::class, 'getStatus']);
});
