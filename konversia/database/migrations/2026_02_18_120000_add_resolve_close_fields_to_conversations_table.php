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
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropForeign(['closed_by']);
            $table->dropColumn(['resolved_at', 'resolved_by', 'closed_at', 'closed_by']);
        });
    }
};