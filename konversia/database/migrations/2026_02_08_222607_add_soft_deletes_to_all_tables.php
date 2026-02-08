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
        // Soft deletes para todas as tabelas principais
        $tables = [
            'companies',
            'departments',
            'users',
            'user_departments',
            'whatsapp_numbers',
            'whatsapp_sessions',
            'contacts',
            'conversations',
            'conversation_transfers',
            'messages'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->softDeletes();
                $table->index(['deleted_at']);
            });
        }

        // Constraints removidas - SQLite não suporta CHECK constraints
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'companies',
            'departments',
            'users',
            'user_departments',
            'whatsapp_numbers',
            'whatsapp_sessions',
            'contacts',
            'conversations',
            'conversation_transfers',
            'messages'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropSoftDeletes();
                $table->dropIndex(['deleted_at']);
            });
        }

        // Constraints removidas - SQLite não suporta CHECK constraints
    }
};
