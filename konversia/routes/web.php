<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppNumberController;
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

// Rotas para Super Admin (fora do middleware company.access)
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->prefix('admin')->name('admin.')->group(function () {
    // Empresas
    Route::resource('companies', \App\Http\Controllers\CompanyController::class);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'company.access',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('whatsapp-numbers')->name('whatsapp-numbers.')->controller(WhatsAppNumberController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{whatsappNumber}/qr', 'showQR')->name('qr');
        Route::post('{whatsappNumber}/connect', 'connect')->name('connect');
        Route::post('{whatsappNumber}/disconnect', 'disconnect')->name('disconnect');
        Route::get('{whatsappNumber}/status', 'checkStatus')->name('status');
    });
    // Conversas
    Route::resource('conversations', ConversationController::class)->only(['index', 'show']);
    Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'getMessages'])->name('conversations.messages.index');
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->name('conversations.messages.store');

    // Usuários (apenas para donos de empresa)
    Route::resource('users', UserController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.update-status');

    // Verificação de autenticação
    Route::get('/auth/check', function () {
        return response()->json(['authenticated' => auth()->check()]);
    })->name('auth.check');
});
