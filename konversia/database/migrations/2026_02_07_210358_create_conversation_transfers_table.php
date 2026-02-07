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
        Schema::create('conversation_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained();
            $table->foreignId('from_department_id')->constrained('departments');
            $table->foreignId('to_department_id')->constrained('departments');
            $table->foreignId('from_user_id')->constrained('users');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('transferred_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_transfers');
    }
};
