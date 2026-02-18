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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('whatsapp_session_id')->constrained();
            $table->foreignId('department_id')->constrained();
            $table->foreignId('contact_id')->constrained();
            $table->string('contact_jid');
            $table->string('contact_name')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'closed', 'transferred'])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('transferred_from_department_id')->nullable()->constrained('departments');
            $table->timestamp('transferred_at')->nullable();
            $table->text('transfer_notes')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'contact_jid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
