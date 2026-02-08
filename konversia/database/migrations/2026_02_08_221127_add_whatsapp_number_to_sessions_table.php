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
        Schema::table('whatsapp_sessions', function (Blueprint $table) {
            $table->foreignId('whatsapp_number_id')->constrained();
            // phone_number será removido já que agora está na tabela whatsapp_numbers
            $table->dropColumn('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_sessions', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_number_id']);
            $table->dropColumn('whatsapp_number_id');
            $table->string('phone_number')->nullable();
        });
    }
};
