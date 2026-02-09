<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modificar o enum delivery_status para incluir 'pending' no início
        DB::statement("ALTER TABLE messages MODIFY COLUMN delivery_status ENUM('pending', 'sent', 'delivered', 'read', 'failed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverter para o enum original sem 'pending'
        DB::statement("ALTER TABLE messages MODIFY COLUMN delivery_status ENUM('sent', 'delivered', 'read', 'failed') DEFAULT 'sent'");
    }
};
