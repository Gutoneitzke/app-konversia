<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('phone_number')->unique(); // Número completo com código do país
            $table->string('nickname'); // Apelido do número (ex: "Vendas", "Suporte")
            $table->string('description')->nullable(); // Descrição opcional
            $table->enum('status', ['inactive', 'active', 'connecting', 'connected', 'error', 'blocked'])->default('inactive');
            $table->string('api_key')->unique(); // Chave única para identificação
            $table->json('settings')->nullable(); // Configurações específicas do número
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->text('error_message')->nullable(); // Última mensagem de erro
            $table->boolean('auto_reconnect')->default(true); // Reconexão automática
            $table->integer('reconnect_attempts')->default(0); // Tentativas de reconexão
            $table->timestamp('blocked_until')->nullable(); // Até quando está bloqueado
            $table->timestamps();

            $table->unique(['company_id', 'phone_number']);
            $table->unique(['company_id', 'nickname']);
            $table->index(['company_id', 'status']);
            $table->index(['status', 'last_activity_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_numbers');
    }
};
