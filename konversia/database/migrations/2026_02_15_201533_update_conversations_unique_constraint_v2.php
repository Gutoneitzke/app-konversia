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
        Schema::table('conversations', function (Blueprint $table) {
            // Adicionar nova constraint única incluindo department_id
            $table->unique(['company_id', 'contact_jid', 'department_id'], 'conversations_company_contact_jid_department_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Remover nova constraint única
            $table->dropUnique('conversations_company_contact_jid_department_unique');
        });
    }
};
