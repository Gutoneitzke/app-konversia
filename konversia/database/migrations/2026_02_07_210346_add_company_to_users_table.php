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
            // Adiciona company_id apenas se não existir
            if (!Schema::hasColumn('users', 'company_id')) {
                $table->foreignId('company_id')->constrained();
            }

            // Remove current_team_id apenas se existir
            if (Schema::hasColumn('users', 'current_team_id')) {
                $table->dropColumn('current_team_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');

            // Adiciona current_team_id de volta apenas se não existir
            if (!Schema::hasColumn('users', 'current_team_id')) {
                $table->foreignId('current_team_id')->nullable();
            }
        });
    }
};
