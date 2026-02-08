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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('whatsapp_number_id')->constrained('whatsapp_numbers');
            $table->string('jid'); // WhatsApp ID único (ex: 5511999999999@s.whatsapp.net)
            $table->string('name')->nullable(); // Nome do contato
            $table->string('phone_number')->nullable(); // Telefone formatado
            $table->string('avatar_url')->nullable(); // URL do avatar
            $table->boolean('is_blocked')->default(false); // Se está bloqueado
            $table->boolean('is_business')->default(false); // Se é conta business
            $table->timestamp('last_seen')->nullable(); // Última vez visto online
            $table->json('metadata')->nullable(); // Informações extras do WhatsApp
            $table->timestamps();

            // Índices para performance
            $table->index(['company_id', 'whatsapp_number_id']);
            $table->index(['company_id', 'jid']); // Para buscar contato específico por empresa

            // Unique: um contato específico não pode ser duplicado para o mesmo número WhatsApp
            $table->unique(['company_id', 'whatsapp_number_id', 'jid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
