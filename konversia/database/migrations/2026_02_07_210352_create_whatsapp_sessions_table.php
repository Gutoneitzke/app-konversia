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
        Schema::create('whatsapp_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('session_id')->unique();
            $table->enum('status', ['disconnected', 'connecting', 'connected', 'error'])->default('disconnected');
            $table->string('phone_number')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_sessions');
    }
};
