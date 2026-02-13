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
        // Para SQLite, vamos usar uma abordagem diferente
        if (DB::getDriverName() === 'sqlite') {
            // SQLite não suporta MODIFY COLUMN com ENUM, então vamos usar TEXT
            Schema::table('messages', function (Blueprint $table) {
                $table->text('delivery_status')->default('pending')->change();
            });
        } else {
            // Para outros bancos (MySQL, PostgreSQL), usar ENUM
            DB::statement("ALTER TABLE messages MODIFY COLUMN delivery_status ENUM('pending', 'sent', 'delivered', 'read', 'failed') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('messages', function (Blueprint $table) {
                $table->text('delivery_status')->default('sent')->change();
            });
        } else {
            DB::statement("ALTER TABLE messages MODIFY COLUMN delivery_status ENUM('sent', 'delivered', 'read', 'failed') DEFAULT 'sent'");
        }
    }
};
