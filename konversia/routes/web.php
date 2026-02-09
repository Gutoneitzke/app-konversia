<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'company.access',
])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // Números WhatsApp
    Route::resource('whatsapp-numbers', \App\Http\Controllers\WhatsAppNumberController::class)->only(['index']);
    Route::get('/whatsapp-numbers/{whatsappNumber}/qr', [\App\Http\Controllers\WhatsAppNumberController::class, 'showQR'])->name('whatsapp-numbers.qr');
    Route::post('/whatsapp-numbers/{whatsappNumber}/connect', [\App\Http\Controllers\WhatsAppNumberController::class, 'connect'])->name('whatsapp-numbers.connect');
    Route::post('/whatsapp-numbers/{whatsappNumber}/disconnect', [\App\Http\Controllers\WhatsAppNumberController::class, 'disconnect'])->name('whatsapp-numbers.disconnect');
    Route::get('/whatsapp-numbers/{whatsappNumber}/status', [\App\Http\Controllers\WhatsAppNumberController::class, 'checkStatus'])->name('whatsapp-numbers.status');

    // Conversas
    Route::resource('conversations', \App\Http\Controllers\ConversationController::class)->only(['index', 'show']);
    Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\MessageController::class, 'store'])->name('conversations.messages.store');

    // Verificação de autenticação
    Route::get('/auth/check', function () {
        return response()->json(['authenticated' => auth()->check()]);
    })->name('auth.check');
});
