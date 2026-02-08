<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsAppController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rotas WhatsApp (sem autenticação - comunicação interna com Node.js)
Route::prefix('whatsapp')->group(function () {
    Route::post('/qr', [WhatsAppController::class, 'receiveQR']);
    Route::post('/message', [WhatsAppController::class, 'receiveMessage']);
    Route::post('/status', [WhatsAppController::class, 'updateStatus']);
});
