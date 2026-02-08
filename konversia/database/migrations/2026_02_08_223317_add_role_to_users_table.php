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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'company_owner', 'employee'])->default('employee')
                  ->after('company_id');
            $table->boolean('is_owner')->default(false)
                  ->after('role'); // Para identificar rapidamente donos de empresa
            $table->index(['role']);
            $table->index(['company_id', 'role']);
        });

        // Para SQLite, vamos permitir que super_admin tenha company_id null via aplicação
        // A validação será feita no Model
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['company_id', 'role']);
            $table->dropColumn(['role', 'is_owner']);
        });
    }
};
