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
        // Para SQLite, vamos recriar a tabela com company_id nullable
        Schema::table('users', function (Blueprint $table) {
            // Remover a constraint existente
            $table->dropForeign(['company_id']);
            // Recriar como nullable
            $table->foreignId('company_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Voltar a ser NOT NULL
            $table->foreignId('company_id')->nullable(false)->change();
        });
    }
};
